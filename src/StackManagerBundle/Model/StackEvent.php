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
        string $resourceLogicalId,
        string $resourcePhysicalId,
        string $resourceType,
        string $resourceStatus,
        string $resourceStatusReason
    ) {
        $this->stack = $stack;
        $this->occurranceTime = $occurranceTime;
        $this->resourceLogicalId = $resourceLogicalId;
        $this->resourcePhysicalId = $resourcePhysicalId;
        $this->resourceType = $resourceType;
        $this->resourceStatus = $resourceStatus;
        $this->resourceStatusReason = $resourceStatusReason;
    }

    public static function newFromApiResponseElement(Stack $stack, array $element): StackEvent
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
    public function getStack(): Stack
    {
        return $this->stack;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getOccurranceTime(): DateTimeImmutable
    {
        return $this->occurranceTime;
    }

    /**
     * @return string
     */
    public function getResourceLogicalId(): string
    {
        return $this->resourceLogicalId;
    }

    /**
     * @return string
     */
    public function getResourcePhysicalId(): string
    {
        return $this->resourcePhysicalId;
    }

    /**
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    /**
     * @return string
     */
    public function getResourceStatus(): string
    {
        return $this->resourceStatus;
    }

    /**
     * @return string
     */
    public function getResourceStatusReason(): string
    {
        return $this->resourceStatusReason;
    }
}
