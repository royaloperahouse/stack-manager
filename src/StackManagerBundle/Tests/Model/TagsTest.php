<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Tests\TwigExtension;

use PHPUnit_Framework_TestCase;
use ROH\Bundle\StackManagerBundle\Model\Tags;

/**
 * Test suite for the Tags model.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class TagsTest extends PHPUnit_Framework_TestCase
{
    public function testToCloudFormationRequestArgument()
    {
        $model = new Tags(['Foo' => 'Bar']);
        $this->assertEquals(
            [['Key' => 'Foo', 'Value' => 'Bar']],
            $model->toCloudFormationRequestArgument()
        );
    }

    public function testIsIdentical()
    {
        $modelA = new Tags(['Foo' => 'Bar']);
        $modelB = clone $modelA;
        $this->assertTrue($modelA->isIdentical($modelB));
    }

    public function testIsNotIdentical()
    {
        $modelA = new Tags(['Foo' => 'Bar']);
        $modelB = new Tags(['Baz' => 'Qux']);
        $this->assertFalse($modelA->isIdentical($modelB));
    }
}
