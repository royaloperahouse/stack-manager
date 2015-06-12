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
     * @var integer
     */
    private $position = 0;

    /**
     * @var array
     */
    private $keys;

    /**
     * @var array
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
    public static function newFromCloudFormationResponseElement(array $response)
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
    public function toCloudFormationRequestArgument()
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

    public function toArray()
    {
        return $this->parameters;
    }

    public function current()
    {
        return $this->parameters[$this->key()];
    }

    public function key()
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

    public function valid()
    {
        return isset($this->keys[$this->position]);
    }
}
