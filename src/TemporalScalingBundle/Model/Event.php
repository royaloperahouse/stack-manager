<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\TemporalScalingBundle\Model;

use DateTime;

/**
 * Immutable model representing a simple event.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class Event
{
    /**
     * @var string
     */
    protected $summary;

    /**
     * @var DateTime
     */
    protected $startTime;

    /**
     * @var DateTime
     */
    protected $endTime;

    /**
     * @param string $name
     * @param DateTime $startTime
     * @param DateTime $endTime
     */
    public function __construct(string $summary, DateTime $startTime, DateTime $endTime)
    {
        $this->summary = $summary;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    /**
     * @return string
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    /**
     * @return DateTime
     */
    public function getStartTime(): DateTime
    {
        return $this->startTime;
    }

    /**
     * @return DateTime
     */
    public function getEndTime(): DateTime
    {
        return $this->endTime;
    }

    /**
     * Duration of the event in seconds.
     *
     * @return int Number of seconds between the start and end of the event.
     */
    public function getDuration(): int
    {
        return $this->getEndTime()->getTimestamp() - $this->getStartTime()->getTimestamp();
    }
}
