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

use ROH\Bundle\StackManagerBundle\Mapper\StackConfigMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to preview the template, parameters and tags of a new stack
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class PreviewStackCommand extends Command
{
    /**
     * @var StackConfigMapper
     */
    private $configStackMapper;

    /**
     * @var StackManagerService
     */
    private $stackManager;

    public function __construct(StackConfigMapper $configStackMapper)
    {
        $this->configStackMapper = $configStackMapper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('stack-manager:preview-stack')
            ->setDescription('Preview a new stack')
            ->addArgument(
                'template',
                InputArgument::REQUIRED,
                'Template of the stack'
            )
            ->addArgument(
                'environment',
                InputArgument::REQUIRED,
                'Environment of the stack'
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
        $scalingProfile = $input->getOption('scaling-profile');
        $name = $input->getOption('name');

        $stack = $this->configStackMapper->create(
            $template,
            $environment,
            $scalingProfile,
            $name
        );

        $output->writeLn('<info>Template</info>');
        $output->write($stack->getTemplate()->getBodyJSON());
        $output->writeLn('');

        $output->writeLn('<info>Parameters</info>');
        $table = new Table($output);
        $table->setHeaders(['Key', 'Value']);
        foreach ($stack->getParameters() as $key => $value) {
            $table->addRow([$key, $value]);
        }
        $table->render();
        $output->writeLn('');

        $output->writeLn('<info>Tags</info>');
        $table = new Table($output);
        $table->setHeaders(['Key', 'Value']);
        foreach ($stack->getTags() as $key => $value) {
            $table->addRow([$key, $value]);
        }
        $table->render();
    }
}
