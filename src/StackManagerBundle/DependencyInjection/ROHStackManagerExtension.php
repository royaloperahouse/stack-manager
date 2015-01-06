<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * Load configuration from the app/Resources/config directory, add stack
 * manager configuration to the service container as parameters and load the
 * stack manager services.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class ROHStackManagerExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Configuration files for the stack manager are located in
        // 'app/Resources/config/*.yml.  Merge them together manually to avoid
        // them being mangled.
        $stackManagerConfig = [];
        $path = $container->getParameter('kernel.root_dir') . '/Resources/config';
        $finder = Finder::create()->files()->name('*.yml')->in($path);
        foreach ($finder as $file) {
            $config = Yaml::parse($file->getPathname());
            foreach ($config as $key => $data) {
                if (isset($stackManagerConfig[$key])) {
                    $stackManagerConfig[$key] = $stackManagerConfig[$key] + $config[$key];
                } else {
                    $stackManagerConfig[$key] = $config[$key];
                }
            }
        }
        $configs[] = $stackManagerConfig;

        // Load stack manager configuration in to parameters for access via the
        // dependency injection container.
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        foreach ($config as $key => $value) {
            $container->setParameter('roh_stack_manager.' . $key, $value);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

    }
}
