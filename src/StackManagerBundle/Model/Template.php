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

use Closure;
use RuntimeException;
use Symfony\Component\Serializer;
use stdClass;

/**
 * Immutable model representing the template of a stack.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class Template
{
    /**
     * Options to pass to json_encode() when converting a template object to
     * JSON.  This should create JSON that is as human readable as possible (we
     * will be looking at these, and don't need to optimise for size) and are
     * safe from being mangled when being translated between JSON and PHP
     * objects.
     *
     * This option corresponds to the value:
     *     JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_BIGINT_AS_STRING
     */
    const JSON_OPTIONS = 194;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var stdClass
     */
    protected $body;

    /**
     * @param string $name
     * @param stdClass|Closure $body
     */
    public function __construct($name, $body)
    {
        $this->name = $name;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return stdClass
     */
    public function getBody()
    {
        if ($this->body instanceof Closure) {
            $closure = $this->body;
            $this->body = $closure();
        } elseif (!$this->body instanceof stdClass) {
            throw new RuntimeException(sprintf(
                'Template body must be either a Closure or stdClass, is of type "%s"',
                gettype($this->body)
            ));
        }

        return $this->body;
    }

    /**
     * @return string JSON representation of the template.
     */
    public function getBodyJSON()
    {
        $json = (new Serializer\Encoder\JsonEncode)->encode(
            $this->getBody(),
            Serializer\Encoder\JsonEncoder::FORMAT,
            ['json_encode_options' => Template::JSON_OPTIONS]
        );

        // Ensure there is a trailing new line to improve output on the console.
        $json .= "\n";

        return $json;
    }

    public function isIdentical(self $template)
    {
        return (
            $this->getName() === $template->getName()
            && $this->getBodyJSON() === $template->getBodyJSON()
        );
    }
}
