<?php


namespace MKDF\Stream\Controller\Plugin\Factory;

use MKDF\Stream\Controller\Plugin\StreamRepositoryPlugin;
use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;

class StreamRepositoryPluginFactory
{
    public function __invoke($container)
    {
        $repository = $container->get(MKDFStreamRepositoryInterface::class);
        return new StreamRepositoryPlugin($repository);
    }
}