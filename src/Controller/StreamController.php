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
        $streamExists = false;
        $streamSummary = $this->_repository->getStreamExists($id);
        $keys = [];
        if ($can_edit) {
            $keys = $this->_keys_repository->findAllUserKeys($user_id);
            $activationParams = ['id' => $id, 'action' => 'activate'];
            $activationLink = $this->url( 'stream', $activationParams, [] );
            //print_r($activationLink);
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [
                ]
            ];

            return new ViewModel([
                'message' => $message,
                'stream_exists' => $streamExists,
                'keys' => $keys,
                'activate_url' => $activationLink,
                'dataset' => $dataset,
                'stream_url' => $this->_repository->getApiHref($dataset->uuid),
                'features' => $this->datasetsFeatureManager()->getFeatures($id),
                'actions' => $actions
            ]);
        }

    }

    public function activateAction() {
        if($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            //print_r($data);
            $id = (int) $this->params()->fromRoute('id', 0);
            $dataset = $this->_dataset_repository->findDataset($id);
            $apiKey = $data['api-key'];
            if($dataset == null){
                throw new \Exception('Not found');
            }
            $user_id = $this->currentUser()->getId();
            $can_edit = ($dataset->user_id == $user_id);
            if($can_edit && $apiKey){
                $outcome = $this->_repository->createDataset($dataset->uuid, $apiKey);
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
