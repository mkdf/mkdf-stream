<?php
namespace MKDF\Stream\Controller\Factory;

use MKDF\Datasets\Repository\MKDFDatasetRepositoryInterface;
use MKDF\Core\Repository\MKDFCoreRepositoryInterface;
use MKDF\Datasets\Service\DatasetPermissionManagerInterface;
use MKDF\Keys\Repository\MKDFKeysRepositoryInterface;
use MKDF\Policies\Repository\PoliciesRepository;
use MKDF\Stream\Controller\StreamController;
use MKDF\Stream\Repository\Factory\MKDFStreamRepositoryFactory;
use MKDF\Stream\Repository\MKDFStreamRepositoryInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Zend\Session\SessionManager;

class StreamControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get("Config");
        $repository = $container->get(MKDFStreamRepositoryInterface::class);
        //$core_repository = $container->get(MKDFCoreRepositoryInterface::class);
        $dataset_repository = $container->get(MKDFDatasetRepositoryInterface::class);
        $keys_repository = $container->get(MKDFKeysRepositoryInterface::class);
        $policies_repository = $container->get(PoliciesRepository::class);
        $sessionManager = $container->get(SessionManager::class);
        $permissionManager = $container->get(DatasetPermissionManagerInterface::class);
        return new StreamController($keys_repository, $dataset_repository, $repository, $policies_repository, $config, $permissionManager);
    }
}