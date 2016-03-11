<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Service;

use DateTimeImmutable;
use ROH\Bundle\StackManagerBundle\Mapper\StackEventApiMapper;
use ROH\Bundle\StackManagerBundle\Model\ApiStack;
use ROH\Bundle\StackManagerBundle\Model\StackEvent;
use ROH\Bundle\StackManagerBundle\Model\Stack;

/**
 * Service to watch CloudFromation stacks for updates.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class StackWatcherService
{
    /**
     * @var StackEventApiMapper
     */
    protected $stackEventMapper;

    /**
     * @var string[] Events on the stack where watching should cease.
     */
    const BREAK_EVENTS = [
        'CREATE_FAILED',
        'CREATE_COMPLETE',
        'DELETE_FAILED',
        'DELETE_COMPLETE',
        'ROLLBACK_FAILED',
        'ROLLBACK_COMPLETE',
        'UPDATE_COMPLETE',
        'UPDATE_ROLLBACK_COMPLETE',
        'UPDATE_ROLLBACK_FAILED',
    ];

    /**
     * @var int Interval between checking for new stack events.
     */
    const POLL_INTERVAL = 5;

    public function __construct(StackEventApiMapper $stackEventMapper)
    {
        $this->stackEventMapper = $stackEventMapper;
    }

    /**
     * Poll stack events for a given stack and pass them to a callback until
     * an appropriate event to break is seen on the stack itself.
     *
     * @param ApiStack $stack Stack to poll for events.
     * @param callable $callback Callback to pass the StackEvent model to.
     * @param string[] $breakEvents Events on the stack to stop watching after.
     * @param int $pollInterval Interval between polling for stack events.
     */
    public function watch(
        ApiStack $stack,
        callable $callback,
        $breakEvents = self::BREAK_EVENTS,
        $pollInterval = self::POLL_INTERVAL
    ) {
        $afterTime = new DateTimeImmutable;

        while (true) {
            $events = $this->stackEventMapper->findStackEventAfterDateTime($stack, $afterTime);

            foreach ($events as $event) {
                $callback($event);

                if ($event->getResourcePhysicalId() === $stack->getId()
                    && in_array($event->getResourceStatus(), $breakEvents)
                ) {
                    return;
                }
            }

            // Check for new events that occur after the time of the last event.
            if (isset($event)) {
                $afterTime = $event->getOccurranceTime();
            }

            sleep($pollInterval);
        }
    }
}
