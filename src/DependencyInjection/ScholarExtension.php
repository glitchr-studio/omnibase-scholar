<?php

namespace Base\Scholar\DependencyInjection;

use Base\Bundle\AbstractBaseExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ScholarExtension extends AbstractBaseExtension
{
    public function getConfiguration(array $config, ContainerBuilder $container): ScholarConfiguration
    {
        return new ScholarConfiguration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.php');

        $configuration = new ScholarConfiguration();
        $config = (new Processor())->processConfiguration($configuration, $configs);

        // Flat parameters: scholar.max_per_source, scholar.metrics_source...
        $this->setConfiguration($container, $config, $configuration->getTreeBuilder()->buildTree()->getName());
    }
}
