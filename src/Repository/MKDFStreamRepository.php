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
        //return ($this->_config['mkdf-stream']['public-url'] . '/data/query/' . $uuid);
        return ('[GET] ' . $this->_config['mkdf-stream']['public-url'] . '/object/' . $uuid);
    }

    public function getApiWriteHref($uuid) {
        //return ($this->_config['mkdf-stream']['public-url'] . '/data/object/' . $uuid);
        return ('[POST] ' . $this->_config['mkdf-stream']['public-url'] . '/object/' . $uuid);
    }

    public function getApiBrowseHref($uuid) {
        return ('[GET] ' . $this->_config['mkdf-stream']['public-url'] . '/browse/' . $uuid);
    }

    public function getApiHome() {
        return ($this->_config['mkdf-stream']['public-url']);
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

    public function addReadWritePermission($uuid,$key) {
        $this->setPermission($uuid,$key,"a");
    }

    public function addWritePermission($uuid,$key) {
        $this->setPermission($uuid,$key,"w");
    }

    public function setPermission($uuid, $key, $permission){
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

        $this->sendQuery("POST",$path, array('dataset-id'=>$uuid, 'read'=>$read, 'write'=>$write));
    }

    public function getDocument ($dataset,$docId,$key=null) {
        $username = $this->_config['mkdf-stream']['user'];
        $password = $this->_config['mkdf-stream']['pass'];
        if(!is_null($key)){
            $username = $key;
            $password = $key;
        }
        $server = $this->_config['mkdf-stream']['server-url'];
        $path = '/object/'.$dataset.'/'.$docId;
        $url = $server . $path;
        //print($url);
        $curl = curl_init();

        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic '. base64_encode($username.":".$password) // <---
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function getDocuments ($dataset,$numDocs,$key = null ,$query = '{}') {
        $username = $this->_config['mkdf-stream']['user'];
        $password = $this->_config['mkdf-stream']['pass'];
        if(!is_null($key)){
            $username = $key;
            $password = $key;
        }
        $server = $this->_config['mkdf-stream']['server-url'];
        $path = '/object/'.$dataset;
        $url = $server . $path;

        $parameters = array('query' => $query);

        $url = $url . '?' . http_build_query($parameters);
        $curl = curl_init();

        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic '. base64_encode($username.":".$password) // <---
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));

        //print($url);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function browseDocuments ($dataset,$query = '{}', $fields = null) {
        $username = $this->_config['mkdf-stream']['user'];
        $password = $this->_config['mkdf-stream']['pass'];
        //$username = $key;
        //$password = $key;
        $server = $this->_config['mkdf-stream']['server-url'];
        $path = '/browse/'.$dataset;
        $url = $server . $path;

        $parameters = array('query' => $query);

        if ($fields) {
            $parameters['fields'] = $fields;
        }

        $url = $url . '?' . http_build_query($parameters);
        $curl = curl_init();

        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic '. base64_encode($username.":".$password) // <---
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function deleteDocument($dataset,$docID,$key) {
        $username = $key;
        $password = $key;
        $server = $this->_config['mkdf-stream']['server-url'];
        $path = '/object/'.$dataset.'/'.$docID;
        $url = $server . $path;

        $curl = curl_init();

        $headers = array(
            'Authorization: Basic '. base64_encode($username.":".$password)
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        //curl_close($curl);
        return curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
    }

    public function pushDocument ($dataset,$document,$key=null) {
        $username = $this->_config['mkdf-stream']['user'];
        $password = $this->_config['mkdf-stream']['pass'];
        $server = $this->_config['mkdf-stream']['server-url'];
        $path = '/object/'.$dataset;
        $url = $server . $path;
        $curl = curl_init();

        if(!is_null($key)){
            $username = $key;
            $password = $key;
        }

        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic '. base64_encode($username.":".$password) // <---
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>$document,
            CURLOPT_HTTPHEADER =>$headers,
        ));

        $response = curl_exec($curl);
        $responseCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        $returnObj = [
            'responseText' => $response,
            'responseCode' => $responseCode
        ];
        return $returnObj;
    }

    public function updateDocument ($dataset,$document,$documentId,$key=null) {
        $username = $this->_config['mkdf-stream']['user'];
        $password = $this->_config['mkdf-stream']['pass'];
        $server = $this->_config['mkdf-stream']['server-url'];
        $path = '/object/'.$dataset.'/'.$documentId;
        $url = $server . $path;
        $curl = curl_init();

        if(!is_null($key)){
            $username = $key;
            $password = $key;
        }

        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic '. base64_encode($username.":".$password) // <---
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS =>$document,
            CURLOPT_HTTPHEADER =>$headers,
        ));

        $response = curl_exec($curl);
        $responseCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        $returnObj = [
            'responseText' => $response,
            'responseCode' => $responseCode
        ];
        return $returnObj;
    }

    public function createAccessRequest ($datasetId, $user, $accessLevel, $description) {
        $arDataset = $this->_config['mkdf-stream']['access-requests'];

        $document = [
            'datasetId' => $datasetId,
            'user' => $user,
            'accessLevel' => $accessLevel,
            'request' => $description,
            'status' => 'pending'
        ];

        $returnObj = $this->pushDocument ($arDataset,json_encode($document));
        return $returnObj;
    }

    public function approveAccessRequest ($arId, $description) {
        $arDataset = $this->_config['mkdf-stream']['access-requests'];
        $accessRequest = json_decode($this->getDocument($arDataset,$arId),true)[0];
        $accessRequest['response'] = $description;
        $accessRequest['status'] = 'accepted';
        print_r($accessRequest);

        $response = $this->updateDocument($arDataset, json_encode($accessRequest), $arId);
        return $response;
    }

    public function rejectAccessRequest ($arId, $description) {
        $arDataset = $this->_config['mkdf-stream']['access-requests'];
        $accessRequest = json_decode($this->getDocument($arDataset,$arId),true)[0];
        $accessRequest['response'] = $description;
        $accessRequest['status'] = 'rejected';
        print_r($accessRequest);

        $response = $this->updateDocument($arDataset, json_encode($accessRequest), $arId);
        return $response;
    }

    public function getAccessRequests ($datasetId, $user = null) {
        $username = $this->_config['mkdf-stream']['user'];
        $password = $this->_config['mkdf-stream']['pass'];
        $arDataset = $this->_config['mkdf-stream']['access-requests'];
        $server = $this->_config['mkdf-stream']['server-url'];
        $path = '/object/'.$arDataset;
        $url = $server . $path;

        $query = [
            'datasetId' => $datasetId
        ];
        if (!is_null($user)) {
            $query['user'] = $user;
        }
        $query = json_encode($query);

        $parameters = array('query' => $query);

        $url = $url . '?' . http_build_query($parameters);
        $curl = curl_init();

        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic '. base64_encode($username.":".$password) // <---
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
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
        //$parameters = array_merge(array('user' => $username,'pwd'=>$password), $parameters);
        $url = $server . $path;
        $ch = curl_init();
        $headers = array(
            //'Content-Type:application/json',
            'Authorization: Basic '. base64_encode($username.":".$password) // <---
        );

        switch ($method){
            case "PUT":
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                break;
            case "GET":
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                $url = $url . '?' . http_build_query($parameters);
                curl_setopt($ch, CURLOPT_URL, $url);
                break;
            default:
                //unexpected method
                throw new \Exception("Unexpected method");
        }
        // receive server response ...
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
