<?php
namespace MKDF\Stream\Controller;

use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
use MKDF\Datasets\Service\DatasetPermissionManager;
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
    private $_permissionManager;

    public function __construct(MKDFKeysRepositoryInterface $keysRepository, MKDFDatasetRepositoryInterface $datasetRepository, MKDFStreamRepositoryInterface $repository, array $config, DatasetPermissionManager $permissionManager)
    {
        $this->_config = $config;
        $this->_repository = $repository;
        $this->_dataset_repository = $datasetRepository;
        $this->_keys_repository = $keysRepository;
        $this->_permissionManager = $permissionManager;
    }

    public function detailsAction() {
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        //FIXME - Also make sure this is a stream dataset that we are retrieving.
        $dataset = $this->_dataset_repository->findDataset($id);
        //$permissions = $this->_repository->findDatasetPermissions($id);
        $message = "Dataset: " . $id;
        $actions = [];
        $can_view = $this->_permissionManager->canView($dataset,$user_id);
        $can_read = $this->_permissionManager->canRead($dataset,$user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset,$user_id);
        $streamExists = $this->_repository->getStreamExists($dataset->uuid);
        $keys = [];
        //$userHasKey = false; //Does the user have a key on this stream (ie do they need to see all the API URLs)?
        $userHasKey = $this->_keys_repository->userHasDatasetKey($user_id,$dataset->id);
        $userDatasetKeys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);
        if ($can_view) {
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
                'stream_url' => $this->_repository->getApiReadHref($dataset->uuid),
                'read_url' => $this->_repository->getApiReadHref($dataset->uuid),
                'write_url' => $this->_repository->getApiWriteHref($dataset->uuid),
                'browse_url' => $this->_repository->getApiBrowseHref($dataset->uuid),
                'api_home' => $this->_repository->getApiHome(),
                'features' => $this->datasetsFeatureManager()->getFeatures($id),
                'actions' => $actions,
                'can_edit' => $can_edit,
                'can_read' => $can_read,
                'user_has_key' => $userHasKey,
                'userDatasetKeys' => $userDatasetKeys,
            ]);
        }
        else{
            $this->flashMessenger()->addErrorMessage('Unauthorised to view dataset.');
            return $this->redirect()->toRoute('dataset', ['action'=>'index']);
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

            $can_view = $this->_permissionManager->canView($dataset,$user_id);
            $can_read = $this->_permissionManager->canRead($dataset,$user_id);
            $can_edit = $this->_permissionManager->canEdit($dataset,$user_id);

            if(($dataset == null) || ($key == null)){
                throw new \Exception('Key/Dataset not found');
            }

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

    public function subscribeAction() {
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);

        $can_view = $this->_permissionManager->canView($dataset,$user_id);
        $can_read = $this->_permissionManager->canRead($dataset,$user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset,$user_id);

        if ($can_view && $can_read){
            $keys = [];
            if($this->getRequest()->isPost()) {
                //Process form data and action the subscription
                $data = $this->getRequest()->getPost();
                $keyUuid = $data['api-key'];
                $key = $this->_keys_repository->findKeyFromUuid($keyUuid,$user_id);
                if(($dataset == null) || ($key == null)){
                    throw new \Exception('Key/Dataset not found');
                }

                $this->_repository->addReadPermission($dataset->uuid, $keyUuid);
                $this->_keys_repository->setKeyPermission($key->id, $dataset->id, 'r');
                $this->flashMessenger()->addSuccessMessage('Subscribed to Stream API');
                return $this->redirect()->toRoute('stream', ['action'=>'details','id'=>$id]);
            }
            else {
                //
                $keys = $this->_keys_repository->findAllUserKeys($user_id);
                $message = "";
                $actions = [
                    'label' => 'Actions',
                    'class' => '',
                    'buttons' => [
                    ]
                ];
                $activationParams = ['id' => $id, 'action' => 'subscribe'];
                $activationLink = $this->url( 'stream', $activationParams, [] );
                return new ViewModel([
                    'message' => $message,
                    'keys' => $keys,
                    'dataset' => $dataset,
                    'features' => $this->datasetsFeatureManager()->getFeatures($id),
                    'actions' => $actions,
                    'activate_url' => $activationLink,
                ]);

            }
        }
        else {
            $this->flashMessenger()->addErrorMessage('Unauthorised to subscribe to dataset.');
            return $this->redirect()->toRoute('dataset', ['action'=>'index']);
        }


    }

}
