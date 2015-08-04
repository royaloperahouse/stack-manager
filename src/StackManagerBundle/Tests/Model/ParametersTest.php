<?php

namespace ROH\Bundle\StackManagerBundle\Tests\TwigExtension;

use PHPUnit_Framework_TestCase;
use ROH\Bundle\StackManagerBundle\Model\Parameters;

class ParametersTest extends PHPUnit_Framework_TestCase
{
    public function testNewFromCloudFormationResponseElement()
    {
        $response = [['ParameterKey' => 'Foo', 'ParameterValue' => 'Bar']];
        $model = Parameters::newFromCloudFormationResponseElement($response);
        $this->assertEquals(
            ['Foo' => 'Bar'],
            $model->toArray()
        );
    }

    public function testToCloudFormationRequestArgument()
    {
        $model = new Parameters(['Foo' => 'Bar']);
        $this->assertEquals(
            [['ParameterKey' => 'Foo', 'ParameterValue' => 'Bar']],
            $model->toCloudFormationRequestArgument()
        );
    }

    public function testIsIdentical()
    {
        $modelA = new Parameters(['Foo' => 'Bar']);
        $modelB = clone $modelA;
        $this->assertTrue($modelA->isIdentical($modelB));
    }

    public function testIsNotIdentical()
    {
        $modelA = new Parameters(['Foo' => 'Bar']);
        $modelB = new Parameters(['Baz' => 'Qux']);
        $this->assertFalse($modelA->isIdentical($modelB));
    }
}
