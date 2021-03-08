<?php

namespace MKDF\Stream\Feature;

use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
use MKDF\Datasets\Service\DatasetsFeatureInterface;
use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;

class StreamFeature implements DatasetsFeatureInterface
{
    private $active = false;

    private $_dataset_repository;
    private $_repository;

    public function __construct(MKDFStreamRepositoryInterface $repository, MKDFDatasetRepositoryInterface $datasetRepository)
    {
        $this->_dataset_repository = $datasetRepository;
        $this->_repository = $repository;
    }

    public function getController() {
        return \MKDF\Stream\Controller\StreamController::class;
    }
    public function getViewAction(){
        return 'details';
    }
    public function getEditAction(){
        return 'index';
    }

    public function getViewHref($id){
        return '/dataset/stream/details/'.$id;
    }

    public function getEditHref($id){
        return '/dataset/stream/details/'.$id;
    }

    public function hasFeature($id){
        // Make a DB call for this dataset to see if it's a stream dataset
        $dataset = $this->_dataset_repository->findDataset($id);
        if (strtolower($dataset->type) == 'stream') {
            return true;
        }
        else {
            return false;
        }
    }

    public function getLabel(){
        return '<i class="fas fa-satellite-dish"></i> Stream API';
    }

    public function isActive(){
        return $this->active;
    }

    public function setActive($bool){
        $this->active = !!$bool;
    }

    public function initialiseDataset($id) {
        //$dataset = $this->_dataset_repository->findDataset($id);
        //$uuid = $dataset->uuid;
        //$this->_repository->createDataset($id, null);
        //echo ("initialising stream stuff");
    }

}