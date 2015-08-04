<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Command;

use ROH\Bundle\StackManagerBundle\Mapper\StackApiMapper;
use ROH\Bundle\StackManagerBundle\Mapper\StackConfigMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to check all stacks managed by the stack manager and whether the
 * current configuration has changes compared to the running stack.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class CheckStacksCommand extends Command
{
    /**
     * @param StackApiMapper
     */
    private $apiStackMapper;

    public function __construct(StackApiMapper $apiStackMapper, StackConfigMapper $configStackMapper)
    {
        $this->apiStackMapper = $apiStackMapper;
        $this->configStackMapper = $configStackMapper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('stack-manager:check-stacks')
            ->setDescription('Check whether running stacks differ from the expected configuration')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeLn('<info>Checking all stacks for changes compared to the current configuration, this may take some time.</info>');

        $table = new Table($output);
        $table->setHeaders(['Name', 'Status', 'Last updated', 'Has changes?']);

        $stacks = $this->apiStackMapper->findAll();
        foreach ($stacks as $stack) {
            $configStack = $this->configStackMapper->create(
                $stack->getTemplate()->getName(),
                $stack->getEnvironment(),
                'default',
                $stack->getName()
            );

            $hasChanges = !$stack->isIdentical($configStack);

            // Output a character after each stack is checked as a progress indicator.
            if ($hasChanges) {
                $output->write('<fg=yellow>Y</>');
            } else {
                $output->write('.');
            }

            $table->addRow([
                $stack->getName(),
                $stack->getStatus(),
                $stack->getLastUpdatedTime()->format('c'),
                $hasChanges ? 'Yes' : 'No',
            ]);
        }

        $output->write(PHP_EOL);

        $table->render();
    }
}
