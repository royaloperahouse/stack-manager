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

use InvalidArgumentException;
use ROH\Bundle\StackManagerBundle\Mapper\StackApiMapper;
use ROH\Bundle\StackManagerBundle\Service\StackManagerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to delete a stack.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class DeleteStackCommand extends Command
{
    /**
     * @var StackApiMapper
     */
    private $apiStackMapper;

    /**
     * @var StackManagerService
     */
    private $stackManager;

    public function __construct(
        StackApiMapper $apiStackMapper,
        StackManagerService $stackManager
    ) {
        $this->apiStackMapper = $apiStackMapper;
        $this->stackManager = $stackManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('stack-manager:delete-stack')
            ->setDescription('Delete the specified stack')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Name of the stack to delete'
            )
            ->addOption(
                'really',
                null,
                InputOption::VALUE_NONE,
                'Whether or not you really mean it'
            )
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        if (!$input->getOption('really')) {
            throw new InvalidArgumentException(
                'Will not delete a stack without the "--really" flag'
            );
        }

        $stack = $this->apiStackMapper->create($name);

        $this->stackManager->delete($stack);
    }
}
