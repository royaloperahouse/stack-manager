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

    public function __construct(
        string $name,
        string $environment,
        Template $template,
        Parameters $parameters
    ) {
        assert(strlen($name) > 0, 'Stack name must be at least one character long');
        assert(strlen($name) <= 255, 'Stack name must be no more than 255 characters in length');
        assert(preg_match('#^[a-zA-Z][-a-zA-Z0-9]*$#', $name), 'Stack name must begin with a letter and contain only alphanumeric characters and dashes');

        $this->name = $name;
        $this->environment = $environment;
        $this->template = $template;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @return Template
     */
    public function getTemplate(): Template
    {
        return $this->template;
    }

    /**
     * @return Parameters
     */
    public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    /**
     * @return Tags
     */
    public function getTags(): Tags
    {
        return new Tags([
            self::ENVIRONMENT_TAG => $this->getEnvironment(),
            self::TEMPLATE_TAG => $this->getTemplate()->getName(),
        ]);
    }

    /**
     * The only trivial way to detect child stacks is if their name ends in a
     * token that would be automatically generated (previously, child stacks had
     * no tags, but this was changed in late 2015).
     *
     * @return boolean Whether the stack might be a child stack
     */
    public function isChildStack(): bool
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
    public function isIdentical(self $stack): bool
    {
        return (
            $this->getTemplate()->isIdentical($stack->getTemplate())
            && $this->getParameters()->isIdentical($stack->getParameters())
            && $this->getTags()->isIdentical($stack->getTags())
        );
    }
}
