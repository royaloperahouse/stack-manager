<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\TemporalScalingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration tree for the temporal scaling bundle
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
        $rootNode = $treeBuilder->root('roh_temporal_scaling');

        $rootNode
            ->children()
                ->arrayNode('calendar_sources')
                    ->info('Ids of calendar to use for each template')
                    ->prototype('scalar')
                    ->end()
                ->end()
        ;

        return $treeBuilder;
    }
}
