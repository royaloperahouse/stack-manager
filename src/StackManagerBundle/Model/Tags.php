<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Model;

use Iterator;
use PHPUnit_Framework_Assert;

/**
 * Immutable model repesenting a stacks tags.
 *
 * Note: As of 2015-01-02 it is currently not possible to update the tags of a
 * stack using the CloudFormation API.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class Tags implements Iterator
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
    protected $tags;

    /**
     * @var array
      */
    public function __construct(array $tags)
    {
        PHPUnit_Framework_Assert::assertLessThanOrEqual(
            10, count($tags),
            'Stacks must have no more than ten tags'
        );

        ksort($tags);

        $this->tags = $tags;
        $this->keys = array_keys($tags);
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
                'Key' => $key,
                'Value' => $value,
            ];
        }

        return $argument;
    }

    public function current()
    {
        return $this->tags[$this->key()];
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
