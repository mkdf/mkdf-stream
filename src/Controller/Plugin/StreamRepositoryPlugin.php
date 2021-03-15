<?php


namespace MKDF\Stream\Controller\Plugin;


use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class StreamRepositoryPlugin extends AbstractPlugin
{
    private $_repository;

    public function __construct(MKDFStreamRepositoryInterface $repository)
    {
        $this->_repository = $repository;
    }

    public function __invoke()
    {
        return $this->_repository;
    }
}