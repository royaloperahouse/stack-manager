<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\TemporalScalingBundle\Command;

use Psr\Log;
use ROH\Bundle\StackManagerBundle\Mapper\StackApiMapper;
use ROH\Bundle\TemporalScalingBundle\CalendarSource\AbstractCalendarSource;
use ROH\Bundle\TemporalScalingBundle\Service\TemporalScalingService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Perform temporal scaling on all stacks that have a calendar source specified.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class PerformScalingCommand extends Command
{
    protected $logger;

    protected $apiStackMapper;

    protected $stackComparisonService;

    protected $temporalScalingService;

    protected $calendarSources;

    public function __construct(
        Log\LoggerInterface $logger,
        StackApiMapper $apiStackMapper,
        AbstractCalendarSource $calendarSource,
        TemporalScalingService $temporalScalingService,
        array $calendarSources
    ) {
        $this->logger = $logger;
        $this->apiStackMapper = $apiStackMapper;
        $this->calendarSource = $calendarSource;
        $this->temporalScalingService = $temporalScalingService;
        $this->calendarSources = $calendarSources;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('temporal-scaling:perform-scaling')
            ->setDescription('Scale all stacks according to the scaling profile specified in their calendar source')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stacks = $this->apiStackMapper->findAll();
        foreach ($stacks as $stack) {
            if (!isset($this->calendarSources[$stack->getTemplate()->getName()])) {
                $this->logger->debug(sprintf(
                    'No calendar source for stack "%s" (template "%s"), not applying temporal scaling',
                    $stack->getName(),
                    $stack->getTemplate()->getName()
                ));

                continue;
            }

            $currentEvents = $this->calendarSource->getCurrentEvents($this->calendarSources[$stack->getTemplate()->getName()]);

            // Current events are sorted by duration ascending, we select the first
            // one on the basis that if there is more than one current event we
            // prefer the most specific.
            $event = reset($currentEvents);

            // If there is no current event, pass null to the temporal scaling
            // service to use the default scaling profile.
            if (!$event) {
                $event = null;
            }

            $this->temporalScalingService->scale($stack, $event);
        }
    }
}
