<?php
namespace MKDF\Stream\Controller;

use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
use MKDF\Keys\Repository\MKDFKeysRepositoryInterface;
use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter;
use Zend\View\Model\ViewModel;
use Zend\Session\SessionManager;
use Zend\Session\Container;

class StreamController extends AbstractActionController
{
    private $_config;
    private $_repository;
    private $_dataset_repository;
    private $_keys_repository;

    public function __construct(MKDFKeysRepositoryInterface $keysRepository, MKDFDatasetRepositoryInterface $datasetRepository, MKDFStreamRepositoryInterface $repository, array $config)
    {
        $this->_config = $config;
        $this->_repository = $repository;
        $this->_dataset_repository = $datasetRepository;
        $this->_keys_repository = $keysRepository;
    }

    public function detailsAction() {
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        //FIXME - only retrieve details that the user can see according to permissions
        //FIXME - Also make sure this is a stream dataset that we are retrieving.
        $dataset = $this->_dataset_repository->findDataset($id);
        //$permissions = $this->_repository->findDatasetPermissions($id);
        $message = "Dataset: " . $id;
        $actions = [];
        $can_edit = ($dataset->user_id == $user_id);
        $streamExists = $this->_repository->getStreamExists($dataset->uuid);
        $keys = [];
        $userHasKey = false; //Does the user have a key on this stream (ie do they need to see all the API URLs)?
        if ($can_edit) {
            $docCount = 0;
            $keys = [];
            $activationLink = null;
            if ($streamExists) {
                $docCount = $this->_repository->getDocCount($dataset->uuid)['totalDocs'];
            }
            else {
                $keys = $this->_keys_repository->findAllUserKeys($user_id);
                $activationParams = ['id' => $id, 'action' => 'activate'];
                $activationLink = $this->url( 'stream', $activationParams, [] );
            }
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [
                ]
            ];
            return new ViewModel([
                'message' => $message,
                'stream_exists' => $streamExists,
                'doc_count' => $docCount,
                'keys' => $keys,
                'activate_url' => $activationLink,
                'dataset' => $dataset,
                'stream_url' => $this->_repository->getApiHref($dataset->uuid),
                'features' => $this->datasetsFeatureManager()->getFeatures($id),
                'actions' => $actions,
                'can_edit' => $can_edit,
                'user_has_key' => $userHasKey,
            ]);
        }
        else{
            // FIXME Better handling security
            throw new \Exception('Unauthorized');
        }

    }

    public function activateAction() {
        if($this->getRequest()->isPost()) {
            $user_id = $this->currentUser()->getId();
            $data = $this->getRequest()->getPost();
            //print_r($data);
            $id = (int) $this->params()->fromRoute('id', 0);
            $dataset = $this->_dataset_repository->findDataset($id);
            $keyUuid = $data['api-key'];
            $key = $this->_keys_repository->findKeyFromUuid($keyUuid,$user_id);
            if(($dataset == null) || ($key == null)){
                throw new \Exception('Key/Dataset not found');
            }

            $can_edit = ($dataset->user_id == $user_id);
            if($can_edit && $keyUuid){
                $this->_repository->createDataset($dataset->uuid, $keyUuid);
                $this->_keys_repository->setKeyPermission($key->id, $dataset->id, 'a');
                $this->flashMessenger()->addSuccessMessage('The Stream API was activated successfully.');
                return $this->redirect()->toRoute('stream', ['action'=>'details','id'=>$id]);
            }else{
                // FIXME Better handling security
                throw new \Exception('Unauthorized');
            }
        }
        else {
            throw new \Exception('Invalid form credentials supplied');
        }


    }

}
