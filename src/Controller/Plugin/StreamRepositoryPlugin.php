<?php


namespace MKDF\Stream\Controller\Plugin;


use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;

class StreamRepositoryPlugin
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