<?php

namespace ROH\Bundle\StackManagerBundle\Tests\TwigExtension;

use PHPUnit_Framework_TestCase;
use ROH\Bundle\StackManagerBundle\Model\Tags;

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
