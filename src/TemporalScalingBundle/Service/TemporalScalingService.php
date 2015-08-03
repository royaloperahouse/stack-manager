<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\TemporalScalingBundle\Service;

use Psr\Log;
use ROH\Bundle\StackManagerBundle\Mapper\StackConfigMapper;
use ROH\Bundle\StackManagerBundle\Model\Stack;
use ROH\Bundle\StackManagerBundle\Service\StackManagerService;
use ROH\Bundle\TemporalScalingBundle\Model\Event;

/**
 * Service to scale a stack using a given event as the scaling profile, while
 * applying appropriate sanity checks.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class TemporalScalingService
{
    protected $logger;

    protected $stackManager;

    protected $configStackMapper;

    protected $scalingProfiles;

    protected $minimumUpdateInterval;

    public function __construct(
        Log\LoggerInterface $logger,
        StackManagerService $stackManager,
        StackConfigMapper $configStackMapper,
        array $scalingProfiles,
        $minimumUpdateInterval = 3600
    ) {
        $this->logger = $logger;
        $this->stackManager = $stackManager;
        $this->configStackMapper = $configStackMapper;
        $this->scalingProfiles = $scalingProfiles;
        $this->minimumUpdateInterval = $minimumUpdateInterval;
    }

    /**
     * Update the stack using the scaling profile specified in the supplied event.
     *
     * @param Stack $stack Model of stack to update.
     * @param Event|null $event Model of event specifying the scaling profile,
     *     or null to use the default profile.
     * @return bool Whether the stack has been updated.
     */
    public function scale(Stack $stack, Event $event = null)
    {
        $this->logger->debug(sprintf(
            'Attempting temporal scaling for stack "%s"',
            $stack->getName()
        ));

        $timeSinceLastUpdated = time() - $stack->getLastUpdatedTime()->getTimestamp();
        if ($timeSinceLastUpdated < $this->minimumUpdateInterval) {
            $this->logger->warn(sprintf(
                'Not scaling stack "%s", as it was last updated %d seconds ago (less than the %d second minimum update interval)',
                $stack->getName(),
                $timeSinceLastUpdated,
                $this->minimumUpdateInterval
            ));

            return false;
        }

        if ($stack->getStatus() !== 'CREATE_COMPLETE'
            && $stack->getStatus() !== 'UPDATE_COMPLETE'
            && $stack->getStatus() !== 'ROLLBACK_COMPLETE'
        ) {
            $this->logger->warn(sprintf(
                'Not scaling stack "%s", as its current status ("%s") is not CREATE_COMPLETE, UPDATE_COMPLETE or ROLLBACK_COMPLETE',
                $stack->getName(),
                $stack->getStatus()
            ));

            return false;
        }

        $scalingProfile = $event === null ? 'default' : $event->getSummary();

        if (!isset($this->scalingProfiles[$stack->getTemplate()->getName()][$scalingProfile])) {
            $this->logger->error(sprintf(
                'Not scaling stack "%s", no scaling profile with name "%s" found',
                $stack->getName(),
                $scalingProfile
            ));

            return false;
        }

        $newStack = $this->configStackMapper->create(
            $stack->getTemplate()->getName(),
            $stack->getEnvironment(),
            $scalingProfile,
            $stack->getName()
        );

        $scalingProfileParameterKeys = array_keys($this->scalingProfiles[$stack->getTemplate()->getName()][$scalingProfile]);
        $changedParameterKeys = array_keys(array_diff_assoc($newStack->getParameters()->toArray(), $stack->getParameters()->toArray()));

        if (!$changedParameterKeys) {
            $this->logger->info(sprintf(
                'Not updating stack "%s" using scaling profile "%s" as no parameters have changed',
                $stack->getName(),
                $scalingProfile
            ));

            return false;
        }

        foreach ($changedParameterKeys as $key) {
            if (!in_array($key, $scalingProfileParameterKeys)) {
                $this->logger->warn(sprintf(
                    'Not updating stack "%s" as updating it would affect parameter "%s" that is not part of a scaling profile',
                    $stack->getName(),
                    $key
                ));

                return false;
            }
        }

        $this->logger->info(sprintf(
            'Updating stack "%s" using scaling profile "%s"',
            $stack->getName(),
            $scalingProfile
        ));
        $this->stackManager->update($newStack);

        return true;
    }
}
