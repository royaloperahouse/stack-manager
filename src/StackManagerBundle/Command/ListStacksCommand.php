<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Command;

use ROH\Bundle\StackManagerBundle\Mapper\StackApiMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to list all stacks managed by the stack manager
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class ListStacksCommand extends Command
{
    /**
     * @param StackApiMapper
     */
    private $apiStackMapper;

    public function __construct(StackApiMapper $apiStackMapper)
    {
        $this->apiStackMapper = $apiStackMapper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('stack-manager:list-stacks')
            ->setDescription('List all stacks managed by the stack manager')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Name', 'Status', 'Last updated']);

        $stacks = $this->apiStackMapper->findAll();
        foreach ($stacks as $stack) {
            $table->addRow([
                $stack->getName(),
                $stack->getStatus(),
                $stack->getLastUpdatedTime()->format('c')
            ]);
        }

        $table->render();
    }
}
