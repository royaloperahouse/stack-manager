<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Service;

use Buzz;
use Symfony\Component\Serializer;
use stdClass;

/**
 * Service to take a template and recursively convert any sub-stacks that have
 * a template URL in to have a template body representing that stack.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class TemplateExpansionService
{
    /**
     * Resource type name of sub-stacks in a CloudFormation template.
     */
    const SUB_STACK_RESOURCE_TYPE = 'AWS::CloudFormation::Stack';

    /**
     * @var Buzz\Browser
     */
    protected $browser;

    public function __construct(Buzz\Browser $browser)
    {
        $this->browser = $browser;
    }

    /**
     * Recursively expand the supplied template body.
     *
     * @param string $body JSON-encoded template body to expand.
     * @return string Expanded template body.
     */
    public function getExpandedTemplateBody($body)
    {
        $body = (new Serializer\Encoder\JsonDecode)->decode(
            $body,
            Serializer\Encoder\JsonEncoder::FORMAT
        );
        $this->expandTemplate($body);

        return $body;
    }

    /**
     * Recursively expand the object representing the template.
     *
     * @param stdClass $template Object representing the template.
     */
    public function expandTemplate(stdClass $template)
    {
        if (!isset($template->Resources)) {
            return;
        }

        foreach ($template->Resources as $name => $data) {
            if ($data->Type !== self::SUB_STACK_RESOURCE_TYPE) {
                continue;
            }

            if (!isset($data->Properties)) {
                continue;
            }

            if (!isset($data->Properties->TemplateURL)) {
                continue;
            }

            $data->Properties->TemplateBody = $this->downloadTemplate(
                $data->Properties->TemplateURL
            );
            unset($data->Properties->TemplateURL);

            $this->expandTemplate($data->Properties->TemplateBody);
        }
    }

    /**
     * Download the template with the specified URL.
     *
     * @param string $url URL of template to download.
     * @return string Template body.
     */
    public function downloadTemplate($url)
    {
        $json = $this->browser->get($url)->getContent();
        $template = (new Serializer\Encoder\JsonDecode)->decode(
            $json,
            Serializer\Encoder\JsonEncoder::FORMAT
        );

        return $template;
    }
}
