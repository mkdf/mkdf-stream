<?php
namespace MKDF\Stream\Controller;

use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
use MKDF\Datasets\Service\DatasetPermissionManager;
use MKDF\Keys\Repository\MKDFKeysRepositoryInterface;
use MKDF\Policies\Repository\PoliciesRepository;
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
    private $_policies_repository;
    private $_permissionManager;

    public function __construct(MKDFKeysRepositoryInterface $keysRepository, MKDFDatasetRepositoryInterface $datasetRepository, MKDFStreamRepositoryInterface $repository, PoliciesRepository $policies_repository, array $config, DatasetPermissionManager $permissionManager)
    {
        $this->_config = $config;
        $this->_repository = $repository;
        $this->_dataset_repository = $datasetRepository;
        $this->_keys_repository = $keysRepository;
        $this->_policies_repository = $policies_repository;
        $this->_permissionManager = $permissionManager;
    }

    public function detailsAction() {
        $user_id = $this->currentUser()->getId();
        $user_email = $this->currentUser()->getEmail();
        $id = (int) $this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);
        //$permissions = $this->_repository->findDatasetPermissions($id);
        $message = "Dataset: " . $id;
        $actions = [];

        $messages = [];
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            foreach($flashMessenger->getMessages() as $flashMessage) {
                $messages[] = [
                    'type' => 'warning',
                    'message' => $flashMessage
                ];
            }
        }

        $can_view = $this->_permissionManager->canView($dataset,$user_id);
        $can_read = $this->_permissionManager->canRead($dataset,$user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset,$user_id);
        $can_write = $this->_permissionManager->canWrite($dataset,$user_id);
        $streamExists = $this->_repository->getStreamExists($dataset->uuid);
        $keys = [];
        //$userHasKey = false; //Does the user have a key on this stream (ie do they need to see all the API URLs)?
        $userHasKey = $this->_keys_repository->userHasDatasetKey($user_id,$dataset->id);
        $userDatasetKeys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);
        $userDatasetKeyLicenses = $this->_policies_repository->getUserLicenseKeyAssocs($dataset->uuid, $user_email);
        foreach ($userDatasetKeys as $index=>$keyItem) {
            $userDatasetKeys[$index]['license'] = null;
            foreach ($userDatasetKeyLicenses as $licenseAssoc) {
                if ($keyItem['keyUUID'] == $licenseAssoc['key']){
                    $userDatasetKeys[$index]['license'] = $licenseAssoc['license'];
                }
            }
        }
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
                'messages' => $messages,
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
                'can_write' => $can_write,
                'user_has_key' => $userHasKey,
                'userDatasetKeys' => $userDatasetKeys,
            ]);
        }
        else{
            $this->flashMessenger()->addMessage('Unauthorised to view dataset.');
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
                $this->flashMessenger()->addMessage('The Stream API was activated successfully.');
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
        $user_email = $this->currentUser()->getEmail();
        $id = (int) $this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);

        $can_view = $this->_permissionManager->canView($dataset,$user_id);
        $can_read = $this->_permissionManager->canRead($dataset,$user_id);
        $can_write = $this->_permissionManager->canWrite($dataset,$user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset,$user_id);

        $userHasKey = $this->_keys_repository->userHasDatasetKey($user_id,$dataset->id);
        $userDatasetKeys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);

        if ($can_view && ($can_read || $can_write)){
            $keys = [];
            if($this->getRequest()->isPost()) {
                //Process form data and action the subscription
                $data = $this->getRequest()->getPost();
                $keyUuid = $data['api-key'];
                $accessLevel = $data['access-level'];
                $key = $this->_keys_repository->findKeyFromUuid($keyUuid,$user_id);
                if(($dataset == null) || ($key == null)){
                    $this->flashMessenger()->addMessage('Key/Dataset not found');
                    return $this->redirect()->toRoute('stream', ['action'=>'details','id'=>$id]);
                    //throw new \Exception('Key/Dataset not found');
                }
                //print_r($accessLevel);
                switch ($accessLevel) {
                    case 'r':
                        if ($can_read) {
                            $this->_repository->addReadPermission($dataset->uuid, $keyUuid);
                            $this->_keys_repository->setKeyPermission($key->id, $dataset->id, 'r');
                            $this->flashMessenger()->addMessage('Registered read-only key to dataset API');
                        }
                    break;
                    case 'a':
                        if ($can_read && $can_write) {
                            $this->_repository->addReadWritePermission($dataset->uuid, $keyUuid);
                            $this->_keys_repository->setKeyPermission($key->id, $dataset->id, 'a');
                            $this->flashMessenger()->addMessage('Registered read/write key to dataset API');
                        }
                    break;
                    case 'w':
                        if ($can_write) {
                            $this->_repository->addWritePermission($dataset->uuid, $keyUuid);
                            $this->_keys_repository->setKeyPermission($key->id, $dataset->id, 'w');
                            $this->flashMessenger()->addMessage('Registered write-only key to dataset API');
                        }
                    break;
                    default:
                        throw new \Exception('Unknown key type');
                }
                // Assign a license
                if ($data['license']) {
                    $this->_policies_repository->assignLicenseToKeyAccess($dataset->uuid, $keyUuid, $user_email, $data['license']);
                    $this->flashMessenger()->addMessage('Registered key with license: '.$data['license']);
                }

                return $this->redirect()->toRoute('stream', ['action'=>'details','id'=>$id]);
            }
            else {
                //
                $keys = $this->_keys_repository->findAllUserKeys($user_id);
                $licensesUser = $this->_policies_repository->getDatasetUserLicense($dataset->uuid, $user_email);
                $licensesAll = $this->_policies_repository->getDatasetUserLicense($dataset->uuid, 'all');
                $licenses = array_merge($licensesUser, $licensesAll);
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
                    'licenses' => $licenses,
                    'dataset' => $dataset,
                    'features' => $this->datasetsFeatureManager()->getFeatures($id),
                    'actions' => $actions,
                    'activate_url' => $activationLink,
                    'can_read' => $can_read,
                    'can_write' => $can_write,
                    'user_has_key' => $userHasKey,
                    'userDatasetKeys' => $userDatasetKeys,
                ]);

            }
        }
        else {
            $this->flashMessenger()->addMessage('Unauthorised to subscribe to dataset.');
            return $this->redirect()->toRoute('dataset', ['action'=>'index']);
        }


    }

    public function editkeyAction () {
        $user_id = $this->currentUser()->getId();
        $user_email = $this->currentUser()->getEmail();
        $id = (int) $this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);
        $keyPassed = $this->params()->fromQuery('key', null); //KEY UUID passed on the query line
        if($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $keyPassed = $data['key'];
        }

        $can_view = $this->_permissionManager->canView($dataset,$user_id);
        $can_read = $this->_permissionManager->canRead($dataset,$user_id);
        $can_write = $this->_permissionManager->canWrite($dataset,$user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset,$user_id);

        $userDatasetKeys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);
        $permission = $this->userHasThisKeyOnDataset($keyPassed,$userDatasetKeys);
        if (!$permission) {
            $this->flashMessenger()->addMessage('Edit key failed: You do not have access to this dataset with this key.');
            return $this->redirect()->toRoute('stream', ['action'=>'details', 'id' => $id]);
        }
        if (ctype_upper($permission)) {
            $this->flashMessenger()->addMessage('Edit key failed: This key has been disabled and cannot be edited.');
            return $this->redirect()->toRoute('stream', ['action'=>'details', 'id' => $id]);
        }

        if($this->getRequest()->isPost()) {
            // Process form data
            $data = $this->getRequest()->getPost();
            $license = $data['license'];
            $oldLicense = $data['oldLicense'];
            $keyPassed = $data['key'];
            if ($data['license']) {
                // FIXME - CONTINUE HERE...
                if ($this->_policies_repository->removeLicenseKeyAssoc ($dataset->uuid, $keyPassed)) {
                    $this->_policies_repository->assignLicenseToKeyAccess($dataset->uuid, $keyPassed, $user_email, $license);
                    $this->flashMessenger()->addMessage('Updated key license');
                }
                else {
                    $this->flashMessenger()->addMessage('Error editing key license');
                }
            }
            else {
                $this->flashMessenger()->addMessage('Error: license details not supplied.');
            }
            return $this->redirect()->toRoute('stream', ['action'=>'details','id'=>$id]);
        }
        else {
            // Present form
            $licensesUser = $this->_policies_repository->getDatasetUserLicense($dataset->uuid, $user_email);
            $licensesAll = $this->_policies_repository->getDatasetUserLicense($dataset->uuid, 'all');
            $licenses = array_merge($licensesUser, $licensesAll);
            $message = "";
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [
                ]
            ];
            $activeLicense = $this->_policies_repository->getLicenseKeyAssoc($dataset->uuid, $keyPassed);
            return new ViewModel([
                'message' => $message,
                'licenses' => $licenses,
                'activeLicense' => $activeLicense,
                'dataset' => $dataset,
                'features' => $this->datasetsFeatureManager()->getFeatures($id),
                'actions' => $actions,
                'can_read' => $can_read,
                'can_write' => $can_write,
                'key' => $keyPassed
            ]);
        }

    }

    public function removekeyAction () {
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);
        $keyPassed = $this->params()->fromQuery('key', null); //KEY UUID passed on the query line
        $token = $this->params()->fromQuery('token', null);

        $userDatasetKeys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);
        $permission = $this->userHasThisKeyOnDataset($keyPassed,$userDatasetKeys);
        if (!$permission) {
            $this->flashMessenger()->addMessage('Remove key failed: You do not have access to this dataset with this key.');
            return $this->redirect()->toRoute('stream', ['action'=>'details', 'id' => $id]);
        }

        if (ctype_upper($permission)) {
            $this->flashMessenger()->addMessage('Remove key failed: This key has been disabled and cannot be removed.');
            return $this->redirect()->toRoute('stream', ['action'=>'details', 'id' => $id]);
        }

        if (is_null($token)) {
            $token = uniqid(true);
            $container = new Container('Key_Management');
            $container->delete_token = $token;
            $messages[] = [
                'type'=> 'warning',
                'message' => 'Are you sure you want to remove your key\'s access to this dataset? Applications will no longer have access to the dataset with this key.'
            ];
            return new ViewModel(
                [
                    'dataset' => $dataset,
                    'token' => $token,
                    'key' => $keyPassed,
                    'messages' => $messages
                ]
            );
        }
        else {
            $container = new Container('Key_Management');
            $valid_token = ($container->delete_token == $token);
            if ($valid_token) {
                // Delete key association here...
                $this->_repository->removePermission($dataset->uuid, $keyPassed);
                $this->_keys_repository->removeKeyUUIDPermission($keyPassed, $id);
                //TODO - REMOVE ANY LICENSE ASSOCIATION HERE **********
                if ($this->_policies_repository->removeLicenseKeyAssoc ($dataset->uuid, $keyPassed)) {
                    $this->flashMessenger()->addMessage('Removed key access from dataset.');
                }
                else {
                    $this->flashMessenger()->addMessage('Removed key access from dataset. No license association found.');

                }
                return $this->redirect()->toRoute('stream', ['action'=>'details', 'id' => $id]);
            }
        }
        return false;
    }

    private function userHasThisKeyOnDataset ($key,$userDatasetKeys) {
        //print_r ($userDatasetKeys);
        foreach ($userDatasetKeys as $datasetKey) {
            if ($datasetKey['keyUUID'] == $key) {
                return $datasetKey['permission'];
            }
        }
        return false;
    }

}
