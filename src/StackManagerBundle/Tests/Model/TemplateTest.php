<?php

namespace ROH\Bundle\StackManagerBundle\Tests\TwigExtension;

use stdClass;
use PHPUnit_Framework_TestCase;
use ROH\Bundle\StackManagerBundle\Model\Template;

class TemplateTest extends PHPUnit_Framework_TestCase
{
    public function testGetName()
    {
        $name = 'foo';
        $template = new Template($name, new stdClass);
        $this->assertEquals(
            $name,
            $template->getName()
        );
    }

    public function testGetBodyClosure()
    {
        $closure = function () {
            return new stdClass();
        };

        $template = new Template('foo', $closure);
        $this->assertEquals(
            $closure(),
            $template->getBody()
        );
    }

    public function testGetBodyStdClass()
    {
        $body = new stdClass();

        $template = new Template('foo', $body);
        $this->assertEquals(
            $body,
            $template->getBody()
        );
    }

    public function testGetBodyWrongType()
    {
        $this->setExpectedException('RuntimeException');

        $template = new Template('foo', '');
        $template->getBody();
    }

    public function testGetBodyJSON()
    {
        $body = new stdClass();

        $template = new Template('foo', $body);
        $this->assertEquals(
            // JSON body always includes a trailing new line to improve output.
            json_encode($body) . "\n",
            $template->getBodyJSON()
        );
    }

    public function testFailedGetBodyJSON()
    {
        // Construct a body that is impossible to encode as JSON.
        $body = new stdClass();
        $body->body = $body;

        $this->setExpectedException('RuntimeException');

        $template = new Template('foo', $body);
        $template->getBodyJSON();
    }

    public function testIsIdentical()
    {
        $name = 'foo';
        $body = new stdClass();

        $templateA = new Template('foo', $body);
        $templateB = clone $templateA;

        $this->assertTrue($templateA->isIdentical($templateB));
    }

    public function testIsNotIdenticalName()
    {
        $body = new stdClass();

        $templateA = new Template('foo', $body);
        $templateB = new Template('bar', $body);

        $this->assertFalse($templateA->isIdentical($templateB));
    }

    public function testIsNotIdenticalBody()
    {
        $name = 'foo';

        $bodyA = new stdClass();
        $bodyA->foo = 'bar';

        $bodyB = new stdClass();
        $bodyB->baz = 'qux';

        $templateA = new Template($name, $bodyA);
        $templateB = new Template($name, $bodyB);

        $this->assertFalse($templateA->isIdentical($templateB));
    }
}
