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

use ROH\Bundle\StackManagerBundle\Mapper\StackConfigMapper;
use ROH\Bundle\StackManagerBundle\Service\StackManagerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to create a new stack.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class CreateStackCommand extends Command
{
    protected $configStackMapper;

    protected $stackManager;

    public function __construct(StackConfigMapper $configStackMapper, StackManagerService $stackManager)
    {
        $this->configStackMapper = $configStackMapper;
        $this->stackManager = $stackManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('stack-manager:create-stack')
            ->setDescription('Create a new stack')
            ->addArgument(
                'template',
                InputArgument::REQUIRED,
                'Template of the stack.'
            )
            ->addArgument(
                'environment',
                InputArgument::REQUIRED,
                'The environment of the stack.'
            )
            ->addOption(
                'scaling-profile',
                null,
                InputOption::VALUE_OPTIONAL,
                'Scaling profile to use for the stack',
                'default'
            )
            ->addOption(
                'name',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of the stack'
            )
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $environment = $input->getArgument('environment');
        $template = $input->getArgument('template');
        $name = $input->getOption('name');
        $scalingProfile = $input->getOption('scaling-profile');

        $stack = $this->configStackMapper->create($template, $environment, $scalingProfile, $name);

        $this->stackManager->create($stack);
    }
}
