<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\TemporalScalingBundle\CalendarSource;

/**
 * Abstract calendar source for retrieving data for temporal scaling.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
abstract class AbstractCalendarSource
{
    /**
     * Get all events occurring at this moment, ordered by their duration ascending.
     *
     * @param string Id of calendar to get the current events of.
     * @return array Event models
     */
    abstract public function getCurrentEvents($calendarId);
}
