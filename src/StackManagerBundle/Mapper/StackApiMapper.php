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

use Guzzle\Service\Builder\ServiceBuilder as AwsClient;
use ROH\Bundle\StackManagerBundle\Model\Parameters;
use ROH\Bundle\StackManagerBundle\Model\Stack;
use ROH\Bundle\StackManagerBundle\Model\Template;
use ROH\Bundle\StackManagerBundle\Service\TemplateExpansionService;

/**
 * Mapper to create stack models from the CloudFormation API
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class StackApiMapper
{
    protected $templateExpansionService;

    protected $cloudFormationClient;

    public function __construct(
        TemplateExpansionService $templateExpansionService,
        AwsClient $awsClient
    ) {
        $this->templateExpansionService = $templateExpansionService;
        $this->cloudFormationClient = $awsClient->get('CloudFormation');
    }

    /**
     * Create a Stack model from the specified stack using the AWS CloudFormation API.
     *
     * @param string $name Name of stack to create the stack model from.
     * @return Stack Model representing the stack.
     */
    public function create($name)
    {
        $stack = current($this->cloudFormationClient->DescribeStacks([
            'StackName' => $name,
        ])->get('Stacks'));

        foreach ($stack['Tags'] as $tag) {
            if ($tag['Key'] === Stack::TEMPLATE_TAG) {
                $templateName = $tag['Value'];
            } elseif ($tag['Key'] === Stack::ENVIRONMENT_TAG) {
                $environment = $tag['Value'];
            }
        }

        // Get the stack name from API response, for if its case has been normalised.
        $name = $stack['StackName'];

        // The stack template may contain sub-stacks as URLs, expand these so
        // we have a complete representation of the stack.
        $templateBody = $this->templateExpansionService->getExpandedTemplateBody(
            $this->cloudFormationClient->GetTemplate([
                'StackName' => $name,
            ])['TemplateBody']
        );

        return new Stack(
            $name,
            $environment,
            new Template($templateName, $templateBody),
            Parameters::newFromCloudFormationResponseElement($stack['Parameters'])
        );
    }
}
