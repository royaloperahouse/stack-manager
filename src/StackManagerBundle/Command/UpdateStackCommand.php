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
use ROH\Bundle\StackManagerBundle\Service\StackManagerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to update an existing stack
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class UpdateStackCommand extends Command
{
    /**
     * @var StackApiMapper
     */
    private $apiStackManager;

    /**
     * @var StackConfigMapper
     */
    private $configStackMapper;

    /**
     * @var StackManagerService
     */
    private $stackManager;

    public function __construct(
        StackApiMapper $apiStackMapper,
        StackConfigMapper $configStackMapper,
        StackManagerService $stackManager
    ) {
        $this->apiStackMapper = $apiStackMapper;
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
            ->setName('stack-manager:update-stack')
            ->setDescription('Update the specified stack')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Name of the stack to update'
            )
            ->addOption(
                'scaling-profile',
                null,
                InputOption::VALUE_OPTIONAL,
                'Scaling profile to use for the stack',
                'default'
            )
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $scalingProfile = $input->getOption('scaling-profile');

        $currentStack = $this->apiStackMapper->create($name);

        $newStack = $this->configStackMapper->create(
            $currentStack->getTemplate()->getName(),
            $currentStack->getEnvironment(),
            $scalingProfile,
            $name
        );

        $this->stackManager->update($newStack);
    }
}
