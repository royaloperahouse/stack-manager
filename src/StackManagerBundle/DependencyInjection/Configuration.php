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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration tree for the stack manager
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('roh_stack_manager');

        $rootNode
            ->children()
                ->arrayNode('defaults')
                    ->requiresAtLeastOneElement()
                    ->info('Default configuration values for the templates')
                    ->example('sample')
                    ->prototype('array')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('environments')
                    ->requiresAtLeastOneElement()
                    ->info('Default configuration values for the templates')
                    ->example('sample')
                    ->prototype('array')
                        ->prototype('array')
                            ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('scaling_profiles')
                    ->requiresAtLeastOneElement()
                    ->info('Default configuration values for the templates')
                    ->example('sample')
                    ->prototype('array')
                        ->prototype('array')
                            ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ;

        return $treeBuilder;
    }
}
