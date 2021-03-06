<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration tree for the stack manager bundle.
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
                    ->info('Default parameters for the templates')
                    ->prototype('array')
                        ->prototype('scalar')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('environments')
                    ->requiresAtLeastOneElement()
                    ->info('Environment specific parameters for the templates')
                    ->prototype('array')
                        ->prototype('array')
                            ->prototype('scalar')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('scaling_profiles')
                    ->requiresAtLeastOneElement()
                    ->info('Scaling profile specific parameters for the templates')
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
