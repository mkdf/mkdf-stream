<?php
namespace MKDF\Stream\Controller\Factory;

use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
use MKDF\Keys\Controller\KeyController;
use MKDF\Keys\Repository\MKDFKeysRepositoryInterface;
use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Stream\Controller\StreamController;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Zend\Session\SessionManager;

class StreamControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get("Config");
        //$repository = $container->get(MKDFKeysRepositoryInterface::class);
        $core_repository = $container->get(MKDFCoreRepositoryInterface::class);
        $dataset_repository = $container->get(MKDFDatasetRepositoryInterface::class);
        $sessionManager = $container->get(SessionManager::class);
        return new StreamController($dataset_repository, $core_repository, $config);
    }
}