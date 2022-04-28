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

    public function detailsAction()
    {
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

        if ($can_view) {
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
        } else {
            $this->flashMessenger()->addMessage('Unauthorised to view dataset.');
            return $this->redirect()->toRoute('dataset', ['action' => 'index']);
        }
    }

}