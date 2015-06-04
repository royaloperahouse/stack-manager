<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Service;

use Guzzle\Service\Builder\ServiceBuilder as AwsClient;
use RuntimeException;
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
     * Number of seconds to allow cURL to download each template.
     */
    const TEMPLATE_DOWNLOAD_TIMEOUT = 10;

    /**
     * Resource type name of sub-stacks in a CloudFormation template.
     */
    const SUB_STACK_RESOURCE_TYPE = 'AWS::CloudFormation::Stack';

    /**
     * Recursively expand the supplied template body.
     *
     * @param string $body JSON-encoded template body to expand.
     * @throws RuntimeException If the template body cannot be decoded as JSON.
     * @return string Expanded template body.
     */
    public function getExpandedTemplateBody($body)
    {
        $body = json_decode($body);
        if ($body === null && json_last_error()) {
            throw new RuntimeException(sprintf(
                'Template body could not be decoded as JSON, error: %s',
                json_last_error_msg()
            ));
        }

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
     * @throws RuntimeException If the template cannot be downloaded.
     * @throws RuntimeException If the template body cannot be decoded as JSON.
     * @return string Template body.
     */
    public function downloadTemplate($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TEMPLATE_DOWNLOAD_TIMEOUT);

        $json = curl_exec($ch);
        if ($json === false) {
            throw new RuntimeException(sprintf(
                'Template could not be downloaded from "%s", cURL error: %s',
                $url,
                curl_error()
            ));
        }

        $template = json_decode($json);
        if ($template === null && json_last_error()) {
            throw new RuntimeException(sprintf(
                'Template "%s" could not be decoded as JSON, error: %s',
                $url,
                json_last_error_msg()
            ));
        }

        return $template;
    }
}
