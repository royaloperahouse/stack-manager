<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\TemporalScalingBundle\CalendarSource;

use DateTime;
use DateTimeZone;
use Google_Client;
use Google_Service_Calendar;
use Psr\Log;
use ROH\Bundle\TemporalScalingBundle\Model\Event;

/**
 * Calendar source using the Google Calendar API.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class GoogleCalendarCalendarSource extends AbstractCalendarSource
{
    /**
     * @var Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $applicationName;

    /**
     * @var string
     */
    protected $developerKey;

    public function __construct(
        Log\LoggerInterface $logger,
        $applicationName,
        $developerKey
    ) {
        $this->logger = $logger;
        $this->applicationName = $applicationName;
        $this->developerKey = $developerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentEvents($calendarId)
    {
        $client = new Google_Client();
        $client->setApplicationName($this->applicationName);
        $client->setDeveloperKey($this->developerKey);

            // Force the timezone here to ensure there is no discrepency
            // between the default timezone and the calendar timezone.
            $timezone = new DateTimeZone('UTC');

            // Using the Google Calendar API, 'timeMin' is inclusive and
            // 'timeMax' is exclusive, so to find current events (i.e. events
            // occurring at this exact second) search between now and now + one
            // second.
            $timeMin = new DateTime('now', $timezone);
        $timeMax = new DateTime('+1 second', $timezone);

        $service = new Google_Service_Calendar($client);
        $response = $service->events->listEvents($calendarId, [
                'singleEvents' => true,
                'timeMin' => $timeMin->format('c'),
                'timeMax' => $timeMax->format('c'),
                'timeZone' => $timezone->getName(),
            ]);

        $events = [];
        foreach ($response->getItems() as $item) {
            $events[] = new Event(
                    $item->getSummary(),
                    new DateTime($item->getStart()->getDateTime(), $timezone),
                    new DateTime($item->getEnd()->getDateTime(), $timezone)
                );
        }

            // Sort events by duration ascending.
            usort($events, function ($a, $b) {
                return ($a->getDuration() < $b->getDuration()) ? -1 : 1;
            });

        $this->logger->debug(sprintf(
                'Found %d current events in calendar "%s"',
                count($events),
                $calendarId
            ));

        return $events;
    }
}
