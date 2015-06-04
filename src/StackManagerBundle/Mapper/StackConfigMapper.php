<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Mapper;

use InvalidArgumentException;
use ROH\Bundle\StackManagerBundle\Model\Parameters;
use ROH\Bundle\StackManagerBundle\Model\Stack;
use ROH\Bundle\StackManagerBundle\Model\Template;
use RuntimeException;
use Symfony\Component\Templating\EngineInterface;

/**
 * Mapper to create stack models from the configuration and templates in the
 * stack manager application
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class StackConfigMapper
{
    protected $templating;

    protected $defaultParameters;

    protected $environmentParameters;

    protected $scalingProfileParameters;

    public function __construct(
        EngineInterface $templating,
        array $defaultParameters,
        array $environmentParameters,
        array $scalingProfileParameters
    ) {
        $this->templating = $templating;
        $this->defaultParameters = $defaultParameters;
        $this->environmentParameters = $environmentParameters;
        $this->scalingProfileParameters = $scalingProfileParameters;
    }

    /**
     * Create a stack model with the specified template, environment and scaling
     * profile.
     *
     * @param string $template Template to use to create the stack model.
     * @param string $environment Environment to use to create the stack model.
     * @param string $scalingProfile Scaling profile to use to create the stack
     *     model, or the default if not supplied.
     * @param string $name Name of the stack to create, or
     *     "{Environment}-{Template}-{WeekNumberingYear}W{WeekNumber}" if not
     *     supplied.
     * @return Stack Model representing the stack.
     */
    public function create($template, $environment, $scalingProfile, $name = null)
    {
        if (!isset($this->defaultParameters[$template])) {
            throw new InvalidArgumentException(sprintf(
                'No default parameters found for template "%s".',
                $template
            ));
        }

        if (!isset($this->environmentParameters[$template][$environment])) {
            throw new InvalidArgumentException(sprintf(
                'No parameters found for environment "%s" of template "%s".',
                $environment,
                $template
            ));
        }

        if (!isset($this->scalingProfileParameters[$template][$scalingProfile])) {
            throw new InvalidArgumentException(sprintf(
                'No parameters found for scaling profile "%s" of template "%s".',
                $scalingProfile,
                $template
            ));
        }

        if ($name === null) {
            $name = preg_replace('#[^-a-zA-Z0-9]#', '', sprintf(
                '%s-%s-%dW%d',
                ucwords(str_replace('_', ' ', $environment)),
                ucwords(str_replace('_', ' ', $template)),
                date('o'), // ISO week-numbering year
                date('W') // ISO week number
            ));
        }

        $parameters = array_replace(
            $this->defaultParameters[$template],
            $this->environmentParameters[$template][$environment],
            $this->scalingProfileParameters[$template][$scalingProfile]
        );

        $body = json_decode($this->templating->render(
            sprintf('%s.json.twig', $template),
            [
                'environment' => $environment,
                'parameters' => $parameters,
                'stackParameters' => $parameters,
                'rootStackName' => $name,
            ]
        ));
        if ($body === null && json_last_error()) {
            throw new RuntimeException(sprintf(
                'Template body could not be decoded as JSON, error: %s',
                json_last_error_msg()
            ));
        }

        $stack = new Stack(
            $name,
            $environment,
            new Template($template, $body),
            new Parameters($parameters)
        );

        return $stack;
    }
}
