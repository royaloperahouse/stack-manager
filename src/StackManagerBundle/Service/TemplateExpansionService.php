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

use Aws\CloudFormation;
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

    /**
     * @var CloudFormation\CloudFormationClient
     */
    protected $cloudFormationClient;

    public function __construct(
        Buzz\Browser $browser,
        CloudFormation\CloudFormationClient $cloudFormationClient
    ) {
        $this->browser = $browser;
        $this->cloudFormationClient = $cloudFormationClient;
    }

    /**
     * Recursively expand the supplied template body.
     *
     * @param string $body JSON-encoded template body to expand.
     * @return string Expanded template body.
     */
    public function getExpandedTemplateBody($stackName, $body)
    {
        $body = (new Serializer\Encoder\JsonDecode)->decode(
            $body,
            Serializer\Encoder\JsonEncoder::FORMAT
        );
        $this->expandTemplate($stackName, $body);

        return $body;
    }

    /**
     * Recursively expand the object representing the template.
     *
     * @param stdClass $template Object representing the template.
     */
    protected function expandTemplate($stackName, stdClass $template)
    {
        if (!isset($template->Resources)) {
            return;
        }

        foreach ($template->Resources as $logicalResourceId => $data) {
            if ($data->Type !== self::SUB_STACK_RESOURCE_TYPE) {
                continue;
            }

            $physicalResourceId = $this->cloudFormationClient->describeStackResource([
                'StackName' => $stackName,
                'LogicalResourceId' => $logicalResourceId,
            ])['StackResourceDetail']['PhysicalResourceId'];

            $templateBody = $this->cloudFormationClient->getTemplate([
                'StackName' => $physicalResourceId,
            ])['TemplateBody'];

            $data->Properties->TemplateBody = (new Serializer\Encoder\JsonDecode)->decode(
                $templateBody,
                Serializer\Encoder\JsonEncoder::FORMAT
            );
            unset($data->Properties->TemplateURL);

            $this->expandTemplate($physicalResourceId, $data->Properties->TemplateBody);
        }
    }
}
