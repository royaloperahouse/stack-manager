<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Model;

use DateTimeImmutable;
use PHPUnit_Framework_Assert;

/**
 * Immutable model representing an event on a stack.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class StackEvent
{
    /**
     * @var Stack
     */
    protected $stack;

    /**
     * @var DateTimeImmutable
     */
    protected $occurranceTime;

    /**
     * @var string
     */
    protected $resourceLogicalId;

    /**
     * @var string
     */
    protected $resourcePhysicalId;

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var string
     */
    protected $resourceStatus;

    /**
     * @var string
     */
    protected $resourceStatusReason;

    public function __construct(
        Stack $stack,
        DateTimeImmutable $occurranceTime,
        $resourceLogicalId,
        $resourcePhysicalId,
        $resourceType,
        $resourceStatus,
        $resourceStatusReason
    ) {
        $this->stack = $stack;
        $this->occurranceTime = $occurranceTime;
        $this->resourceLogicalId = $resourceLogicalId;
        $this->resourcePhysicalId = $resourcePhysicalId;
        $this->resourceType = $resourceType;
        $this->resourceStatus = $resourceStatus;
        $this->resourceStatusReason = $resourceStatusReason;
    }

    public static function newFromApiResponseElement(Stack $stack, array $element)
    {
        $event = new StackEvent(
            $stack,
            new DateTimeImmutable($element['Timestamp']),
            $element['LogicalResourceId'],
            $element['PhysicalResourceId'],
            $element['ResourceType'],
            $element['ResourceStatus'],
            isset($element['ResourceStatusReason']) ? $element['ResourceStatusReason'] : ''
        );

        return $event;
    }

    /**
     * @return Stack
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getOccurranceTime()
    {
        return $this->occurranceTime;
    }

    /**
     * @return string
     */
    public function getResourceLogicalId()
    {
        return $this->resourceLogicalId;
    }

    /**
     * @return string
     */
    public function getResourcePhysicalId()
    {
        return $this->resourcePhysicalId;
    }

    /**
     * @return string
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * @return string
     */
    public function getResourceStatus()
    {
        return $this->resourceStatus;
    }

    /**
     * @return string
     */
    public function getResourceStatusReason()
    {
        return $this->resourceStatusReason;
    }
}
