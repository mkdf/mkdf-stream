<?php

namespace MKDF\Stream\Feature;

use MKDF\Datasets\Service\DatasetsFeatureInterface;

class StreamFeature implements DatasetsFeatureInterface
{
    private $active = false;

    public function getController() {
        return \MKDF\Stream\Controller\StreamController::class;
    }
    public function getViewAction(){
        return 'index';
    }
    public function getEditAction(){
        return 'index';
    }
    public function getViewHref($id){
        return '/stream/details/'.$id;
    }
    public function getEditHref($id){
        return '/stream/details/'.$id;
    }
    public function hasFeature($id){
        // Make a DB call for this dataset to see if it's a stream dataset

        return true;
    }
    public function getLabel(){
        return 'Stream details';
    }
    public function isActive(){
        return $this->active;
    }
    public function setActive($bool){
        $this->active = !!$bool;
    }

}