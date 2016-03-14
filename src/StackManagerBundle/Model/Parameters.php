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

use Iterator;

/**
 * Immutable model representing the parameters of a stack.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class Parameters implements Iterator
{
    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var string[]
     */
    private $keys;

    /**
     * @var string[]
     */
    protected $parameters;

    public function __construct(array $parameters)
    {
        ksort($parameters);

        $this->parameters = $parameters;
        $this->keys = array_keys($parameters);
    }

    /**
     * Create a parameters model from the parameters element of a CloudFormation
     * API response.
     *
     * @param array $response Parameters element of a CloudFormation API response.
     * @return Parameters
     */
    public static function newFromCloudFormationResponseElement(array $response): self
    {
        $parameters = [];
        foreach ($response as $parameter) {
            $parameters[$parameter['ParameterKey']] = $parameter['ParameterValue'];
        }

        return new self($parameters);
    }

    /**
     * Convert the model to an array of arguments ready to be passed to the
     * CloudFormation API.
     *
     * @return array Arguments for passing to the CloudFormation API.
     */
    public function toCloudFormationRequestArgument(): array
    {
        $argument = [];
        foreach ($this as $key => $value) {
            $argument[] = [
                'ParameterKey' => $key,
                'ParameterValue' => $value,
            ];
        }

        return $argument;
    }

    /**
     * Whether the set of parameters represented by this model is identical to
     * those represented by the specified model.
     *
     * @param self $parameters
     * @return boolean
     */
    public function isIdentical(self $parameters): bool
    {
        return (
            // The arrays will both be sorted by key so can be compared using a
            // simple (weak) comparison.
            $this->toArray() == $parameters->toArray()
        );
    }

    /* Iterator methods */

    public function toArray(): array
    {
        return $this->parameters;
    }

    public function current(): string
    {
        return $this->parameters[$this->key()];
    }

    public function key(): string
    {
        return $this->keys[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->keys[$this->position]);
    }
}
