<?php


namespace MKDF\Stream\Repository\Factory;

use Interop\Container\ContainerInterface;
use MKDF\Stream\Repository\MKDFStreamRepository;
use Zend\ServiceManager\Factory\FactoryInterface;

class MKDFStreamRepositoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get("Config");
        return new MKDFStreamRepository($config);
    }
}