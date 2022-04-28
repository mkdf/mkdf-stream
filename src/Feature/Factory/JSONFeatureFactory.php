<?php

namespace MKDF\Stream\Feature\Factory;

use Interop\Container\ContainerInterface;
use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
use MKDF\Stream\Feature\JSONFeature;
use MKDF\Stream\Feature\StreamFeature;
use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class JSONFeatureFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get("Config");
        //$repository = $container->get(MKDFKeysRepositoryInterface::class);
        $dataset_repository = $container->get(MKDFDatasetRepositoryInterface::class);
        $streamApi_repository = $container->get(MKDFStreamRepositoryInterface::class);
        return new JSONFeature($streamApi_repository,$dataset_repository);
    }
}