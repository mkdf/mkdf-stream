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

class JSONBrowserController extends AbstractActionController
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

    public function writedocAction()
    {
        $user_id = $this->currentUser()->getId();
        $id = (int)$this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);
        //$permissions = $this->_repository->findDatasetPermissions($id);
        $keyPassed = $this->params()->fromQuery('key', null); //KEY UUID passed on the query line
        $docIDPassed = $this->params()->fromQuery('docid', null); //Doc ID passed on the query line
        $message = "Dataset: " . $id;
        $actions = [];

        $messages = [];
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            foreach ($flashMessenger->getMessages() as $flashMessage) {
                $messages[] = [
                    'type' => 'warning',
                    'message' => $flashMessage
                ];
            }
        }

        $can_view = $this->_permissionManager->canView($dataset, $user_id);
        $can_read = $this->_permissionManager->canRead($dataset, $user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset, $user_id);
        $can_write = $this->_permissionManager->canWrite($dataset, $user_id);
        $streamExists = $this->_repository->getStreamExists($dataset->uuid);
        $keys = [];
        //$userHasKey = false; //Does the user have a key on this stream (ie do they need to see all the API URLs)?
        $userHasKey = $this->_keys_repository->userHasDatasetKey($user_id, $dataset->id);
        $userDatasetKeys = $this->_keys_repository->userDatasetKeys($user_id, $dataset->id);

        if ($can_view && $can_read && $can_write && $userHasKey) {
            $keys = [];
            if (!$streamExists) {
                $this->flashMessenger()->addMessage('This dataset has not yet been activated.');
                return $this->redirect()->toRoute('dataset', ['action' => 'details', 'id' => $id]);
            }
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [
                ]
            ];
            $keys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);
            return new ViewModel([
                'message' => $message,
                'messages' => $messages,
                'stream_exists' => $streamExists,
                'keys' => $keys,
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
                'key' => $keyPassed,
                'docid' => $docIDPassed,
            ]);
        } else {
            $this->flashMessenger()->addMessage('You do not have any suitable keys registered to this dataset for browsing JSON documents. Please use the API tab to register an access key.');
            return $this->redirect()->toRoute('dataset', ['action' => 'details', 'id' => $id]);
        }
    }

    public function detailsAction () {
        $user_id = $this->currentUser()->getId();
        $id = (int)$this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);
        //$permissions = $this->_repository->findDatasetPermissions($id);

        $message = "Dataset: " . $id;
        $actions = [];

        $messages = [];
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            foreach ($flashMessenger->getMessages() as $flashMessage) {
                $messages[] = [
                    'type' => 'warning',
                    'message' => $flashMessage
                ];
            }
        }
        $can_view = $this->_permissionManager->canView($dataset, $user_id);
        $can_read = $this->_permissionManager->canRead($dataset, $user_id);
        $can_edit = $this->_permissionManager->canEdit($dataset, $user_id);
        $can_write = $this->_permissionManager->canWrite($dataset, $user_id);
        $streamExists = $this->_repository->getStreamExists($dataset->uuid);
        $keys = [];
        //$userHasKey = false; //Does the user have a key on this stream (ie do they need to see all the API URLs)?
        $userHasKey = $this->_keys_repository->userHasDatasetKey($user_id, $dataset->id);
        $userDatasetKeys = $this->_keys_repository->userDatasetKeys($user_id, $dataset->id);

        if ($can_view && $can_read && $userHasKey) {
            $docCount = 0;
            $keys = [];
            $activationLink = null;
            if ($streamExists) {
                $docCount = $this->_repository->getDocCount($dataset->uuid)['totalDocs'];
            } else {
                $keys = $this->_keys_repository->findAllUserKeys($user_id);
                $activationParams = ['id' => $id, 'action' => 'activate'];
                $activationLink = $this->url('stream', $activationParams, []);
            }
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [
                ]
            ];
            $keys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);
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
                'userDatasetKeys' => $userDatasetKeys
            ]);
        } else {
            $this->flashMessenger()->addMessage('You do not have any suitable keys registered to this dataset for editing or creating JSON documents. Please use the API tab to register an access key.');
            return $this->redirect()->toRoute('dataset', ['action' => 'details', 'id' => $id]);
        }
    }

    public function deletedocAction () {
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);
        $keyPassed = $this->params()->fromQuery('key', null); //KEY UUID passed on the query line
        $docIDPassed = $this->params()->fromQuery('docid', null); //Doc ID passed on the query line
        $token = $this->params()->fromQuery('token', null);

        $userDatasetKeys = $this->_keys_repository->userDatasetKeys($user_id,$dataset->id);
        $permission = $this->userHasThisKeyOnDataset($keyPassed,$userDatasetKeys);
        if (!$permission) {
            $this->flashMessenger()->addMessage('Delete document failed: You do not have access to this dataset with this key.');
            return $this->redirect()->toRoute('json', ['action'=>'details', 'id' => $id]);
        }

        if (ctype_upper($permission)) {
            $this->flashMessenger()->addMessage('Delete document failed: This key has been disabled.');
            return $this->redirect()->toRoute('stream', ['action'=>'details', 'id' => $id]);
        }

        if (is_null($token)) {
            $token = uniqid(true);
            $container = new Container('JSON_Delete_Doc');
            $container->delete_token = $token;
            $messages[] = [
                'type'=> 'warning',
                'message' => 'Are you sure you want to delete this document? This operation cannot be undone.'
            ];
            return new ViewModel(
                [
                    'dataset' => $dataset,
                    'token' => $token,
                    'key' => $keyPassed,
                    'docid' => $docIDPassed,
                    'messages' => $messages
                ]
            );
        }
        else {
            $container = new Container('JSON_Delete_Doc');
            $valid_token = ($container->delete_token == $token);
            if ($valid_token) {
                // Delete JSON document here...
                $result = $this->_repository->deleteDocument($dataset->uuid,$docIDPassed,$keyPassed);
                if ($result == "204") {
                    $this->flashMessenger()->addMessage('Deleted document from dataset.');
                }
                else {
                    $this->flashMessenger()->addMessage('Failed to delete document from dataset.');
                }
                //$this->_repository->removePermission($dataset->uuid, $keyPassed);
                //$this->_keys_repository->removeKeyUUIDPermission($keyPassed, $id);

                return $this->redirect()->toRoute('json', ['action'=>'details', 'id' => $id]);
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