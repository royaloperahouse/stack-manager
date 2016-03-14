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
    protected $tags;

    /**
     * @var string[]
     */
    public function __construct(array $tags)
    {
        assert(count($tags) <= 10, 'Stacks must have no more than ten tags');

        ksort($tags);

        $this->tags = $tags;
        $this->keys = array_keys($tags);
    }

    /**
     * Convert the model to an array of arguments ready to be passed to the
     * CloudFormation API.
     *
     * @return string[][] Arguments for passing to the CloudFormation API.
     */
    public function toCloudFormationRequestArgument(): array
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

    /**
     * Whether the set of tags represented by this model is identical to those
     * represented by the specified model.
     *
     * @param self $tags
     * @return boolean
     */
    public function isIdentical(self $tags): bool
    {
        return (
            // The arrays will both be sorted by key so can be compared using a
            // simple (weak) comparison.
            $this->toArray() == $tags->toArray()
        );
    }

    /* Iterator methods */

    public function toArray(): array
    {
        return $this->tags;
    }

    public function current(): string
    {
        return $this->tags[$this->key()];
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
