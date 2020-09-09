<?php


namespace MKDF\Stream\Repository;


use PHPUnit\Util\Exception;

class MKDFStreamRepository implements MKDFStreamRepositoryInterface
{
    private $_config;


    public function __construct($config)
    {
        $this->_config = $config;
    }

    public function getApiReadHref($uuid) {
        return ($this->_config['mkdf-stream']['public-url'] . '/query/' . $uuid);
    }

    public function getApiWriteHref($uuid) {
        return ($this->_config['mkdf-stream']['public-url'] . '/object/' . $uuid);
    }

    public function getCollectionList () {
        return $this->sendQuery('GET','/management/datasets', []);
    }

    public function createDataset($uuid, $api_key){
        $this->sendQuery("POST", '/management/datasets', array('dataset-uuid'=>$uuid,'key'=>$api_key));
        return true;
    }

    public function getDocCount($uuid){
        $repsonse = $this->sendQuery('GET','/management/datasets/' . $uuid, array());
        //echo ($repsonse);
        $arr = json_decode($repsonse,true);
        return $arr;
    }

    public function getStreamExists($uuid) {
        $repsonse = $this->sendQuery('GET','/management/datasets/' . $uuid, array());
        //echo ($repsonse);
        $arr = json_decode($repsonse,true);
        if (empty($arr)){
            return false;
        }
        else {
            return true;
        }
    }

    public function removePermission($uuid, $key){
        $this->setPermission($uuid, $key, "d");
    }

    public function addReadPermission($uuid,$key) {
        $this->setPermission($uuid,$key,"r");
    }

    private function setPermission($uuid, $key, $permission){
        switch ($permission) {
            case "a":
                $read = 1;
                $write = 1;
                break;
            case "w":
                $read = 0;
                $write = 1;
                break;
            case "r":
                $read = 1;
                $write = 0;
                break;
            case "d":
                $read = 0;
                $write = 0;
                break;
            default:
                $read = 0;
                $write = 0;
        }
        $path = '/management/permissions/'.$key;
        $this->sendQuery("POST",$path, array('dataset-uuid'=>$uuid, 'read'=>$read, 'write'=>$write));
    }

    /**
     * @param $method
     * @param $path
     * @param $parameters
     * @return bool|string
     * @throws \Exception
     */
    private function sendQuery($method, $path, $parameters) {
        $username = $this->_config['mkdf-stream']['user'];
        $password = $this->_config['mkdf-stream']['pass'];
        $server = $this->_config['mkdf-stream']['server-url'];
        $parameters = array_merge(array('user' => $username,'pwd'=>$password), $parameters);
        $url = $server . $path;
        $ch = curl_init();

        switch ($method){
            case "PUT":
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
                break;
            case "GET":
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                $url = $url . '?' . http_build_query($parameters);
                curl_setopt($ch, CURLOPT_URL, $url);
                break;
            default:
                //unexpected method
                throw new \Exception("Unexpected method");
        }
        // receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);

        if (!curl_errno($ch)) {
            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                case 201:  # OK Created
                    //self::log('Message from API Factory server: ',$server_output);
                    //echo "201";
                    break;
                case 200:  # OK Updated
                    //self::log('Message from API Factory server: ',$server_output);
                    //echo "200";
                    break;
                default:
                    throw new \Exception('Unexpected HTTP code: '. $http_code ."\n\nURL: ". $url . "\n\n" . $server_output);
                    //echo "Something else: ".$http_code;
            }
        }else{
            //self::logErr('Curl Error: ', $curl_errno($ch));
            throw new \Exception('cURL error: '. curl_error($ch) ."\n\nURL: ". $url . "\n\n" . $server_output);
        }
        curl_close ($ch);
        return $server_output;
    }
}
