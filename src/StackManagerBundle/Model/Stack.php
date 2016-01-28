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

use DateTime;
use PHPUnit_Framework_Assert;

/**
 * Immutable model representing a CloudFormation stack.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class Stack
{
    /**
     * Tag to use in CloudFormation for the stack environment name.
     */
    const ENVIRONMENT_TAG = 'roh:stack-manager:environment';

    /**
     * Tag to use in CloudFormation for the stack template name.
     */
    const TEMPLATE_TAG = 'roh:stack-manager:template';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var Template
     */
    protected $template;

    /**
     * @var Parameters
     */
    protected $parameters;

    /**
     * Id in CloudFormation.  Only available for models created from the API.
     *
     * @var string|null
     */
    protected $id;

    /**
     * Status in CloudFormation.  Only available for models created from the
     * API.
     *
     * @var string|null
     */
    protected $status;

    /**
     * Creation time in CloudFormation.  Only available for models created from
     * the API.
     *
     * @var DateTime|null
     */
    protected $creationTime;

    /**
     * Last updated time in CloudFormation.  Only available for models created
     * from the API.
     *
     * @var DateTime|null
     */
    protected $lastUpdatedTime;

    public function __construct(
        $name,
        $environment,
        Template $template,
        Parameters $parameters,
        $id = null,
        $status = null,
        DateTime $creationTime = null,
        DateTime $lastUpdatedTime = null
    ) {
        PHPUnit_Framework_Assert::assertInternalType(
            'string', $name,
            'Stack name must be a string'
        );
        PHPUnit_Framework_Assert::assertInternalType(
            'string', $environment,
            'Stack environment must be a string'
        );
        PHPUnit_Framework_Assert::assertGreaterThan(
            0, strlen($name),
            'Stack name must be at least one character long'
        );
        PHPUnit_Framework_Assert::assertLessThanOrEqual(
            255, strlen($name),
            'Stack name must be no more than 255 characters in length'
        );
        PHPUnit_Framework_Assert::assertRegExp(
            '#^[a-zA-Z][-a-zA-Z0-9]*$#', $name,
            'Stack name must begin with a letter and contain only alphanumeric characters and dashes'
        );

        $this->name = $name;
        $this->environment = $environment;
        $this->template = $template;
        $this->parameters = $parameters;
        $this->id = $id;
        $this->status = $status;
        $this->creationTime = $creationTime;
        $this->lastUpdatedTime = $lastUpdatedTime;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return Template
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return Tags
     */
    public function getTags()
    {
        return new Tags([
            self::ENVIRONMENT_TAG => $this->getEnvironment(),
            self::TEMPLATE_TAG => $this->getTemplate()->getName(),
        ]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCreationTime()
    {
        return $this->creationTime;
    }

    public function getLastUpdatedTime()
    {
        return $this->lastUpdatedTime;
    }

    /**
     * The only trivial way to detect child stacks is if their name ends in a
     * token that would be automatically generated (previously, child stacks had
     * no tags, but this was changed in late 2015).
     *
     * @return boolean Whether the stack might be a child stack
     */
    public function isChildStack()
    {
        return preg_match('#-[A-Z0-9]{12,13}$#', $this->getName());
    }

    /**
     * Check if this stack is identical to the supplied stack object (i.e. has
     * the same template body and parrameters).
     *
     * @param Stack $stack Stack to compare this stack to.
     * @return boolean Whether the two stacks objects are identical.
     */
    public function isIdentical(self $stack)
    {
        return (
            $this->getTemplate()->isIdentical($stack->getTemplate())
            && $this->getParameters()->isIdentical($stack->getParameters())
            && $this->getTags()->isIdentical($stack->getTags())
        );
    }
}
