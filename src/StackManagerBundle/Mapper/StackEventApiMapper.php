<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Mapper;

use Aws\CloudFormation;
use DateTimeInterface;
use ROH\Bundle\StackManagerBundle\Model\Stack;
use ROH\Bundle\StackManagerBundle\Model\StackEvent;

/**
 * Mapper to create stack event models from the CloudFormation API.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class StackEventApiMapper
{
    /**
     * @var CloudFormation\CloudFormationClient
     */
    protected $cloudFormationClient;

    public function __construct(CloudFormation\CloudFormationClient $cloudformationClient)
    {
        $this->cloudformationClient = $cloudformationClient;
    }

    /**
     * Find the events for a stack that occur after a specified time.
     *
     * @param Stack $stack Stack to find events for.
     * @param DateTimeInterface $afterTime Time to find events after.
     * @return StackEvents[] Events occuring after the specified time, ordered
     *     chronologically.
     */
    public function findStackEventAfterDateTime(Stack $stack, DateTimeInterface $afterTime)
    {
        $response = $this->cloudformationClient->DescribeStackEvents([
            // Specifying the stack ID rather than name works for deleted
            // stacks, so use that if it is available.
            'StackName' => $stack instanceof ApiStack ? $stack->getId() : $stack->getName(),
        ]);

        $events = [];
        foreach ($response['StackEvents'] as $responseEvent) {
            $event = StackEvent::newFromApiResponseElement($stack, $responseEvent);
            if ($event->getOccurranceTime() <= $afterTime) {
                continue;
            }

            $events[] = $event;
        }

        // Stack events appear to be returned by the API in reverse
        // chronological order, but this behaviour is undocumented, so order
        // chronologically by comparing timestamps.
        usort($events, function (StackEvent $eventA, StackEvent $eventB) {
            return $eventA->getOccurranceTime() > $eventB->getOccurranceTime();
        });

        return $events;
    }
}
