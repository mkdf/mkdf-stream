<?php
namespace MKDF\Stream\Controller;

use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
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

    public function __construct(MKDFDatasetRepositoryInterface $datasetRepository, MKDFCoreRepositoryInterface $core_repository, array $config)
    {
        $this->_config = $config;
        $this->_core_repository = $core_repository;
        $this->_dataset_repository = $datasetRepository;
    }

    public function detailsAction() {
        $user_id = $this->currentUser()->getId();
        $id = (int) $this->params()->fromRoute('id', 0);
        $dataset = $this->_dataset_repository->findDataset($id);
        //$permissions = $this->_repository->findDatasetPermissions($id);
        $message = "Dataset: " . $id;
        $actions = [];
        $can_edit = ($dataset->user_id == $user_id);
        if ($can_edit) {
            $actions = [
                'label' => 'Actions',
                'class' => '',
                'buttons' => [

                ]
            ];
        }
        return new ViewModel([
            'message' => $message,
            'dataset' => $dataset,
            'features' => $this->datasetsFeatureManager()->getFeatures($id),
            'actions' => $actions
        ]);
    }

}
