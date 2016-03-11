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
 * Immutable model representing a CloudFormation stack that exists or existed in
 * AWS.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class ApiStack extends Stack
{
    /**
     * Id in CloudFormation.
     *
     * @var string|null
     */
    protected $id;

    /**
     * Status in CloudFormation.
     *
     * @var string|null
     */
    protected $status;

    /**
     * Creation time in CloudFormation.
     *
     * @var DateTimeImmutable|null
     */
    protected $creationTime;

    /**
     * Last updated time in CloudFormation.
     *
     * @var DateTimeImmutable|null
     */
    protected $lastUpdatedTime;

    public function __construct(
        $name,
        $environment,
        Template $template,
        Parameters $parameters,
        $id,
        $status,
        DateTimeImmutable $creationTime,
        DateTimeImmutable $lastUpdatedTime
    ) {
        parent::__construct($name, $environment, $template, $parameters);

        $this->id = $id;
        $this->status = $status;
        $this->creationTime = $creationTime;
        $this->lastUpdatedTime = $lastUpdatedTime;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreationTime()
    {
        return $this->creationTime;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getLastUpdatedTime()
    {
        return $this->lastUpdatedTime;
    }
}
