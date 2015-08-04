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
use ROH\Bundle\StackManagerBundle\Model\Parameters;

/**
 * Test suite for the Parameters model.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
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
