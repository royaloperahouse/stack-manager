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

use DateTime;
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

    protected $cloudFormation;

    public function __construct(
        TemplateExpansionService $templateExpansionService,
        AwsClient $awsClient
    ) {
        $this->templateExpansionService = $templateExpansionService;
        $this->cloudFormation = $awsClient->get('CloudFormation');
    }

    /**
     * Create a Stack model from the specified stack using the AWS CloudFormation API.
     *
     * @param string $name Name of stack to create the stack model from.
     * @return Stack Model representing the stack.
     */
    public function create($name)
    {
        $stack = $this->createFromApiResponse(current($this->cloudFormation->DescribeStacks([
            'StackName' => $name,
        ])->get('Stacks')));

        return $stack;
    }

    /**
     * Get Stack models for all stacks with an appropriate template and
     * environment tag using the AWS CloudFormation API.
     *
     * @return array Stack models
     */
    public function findAll()
    {
        $response = $this->cloudFormation->DescribeStacks();
        foreach ($response['Stacks'] as $data) {
            $stack = $this->createFromApiResponse($data);
            if ($stack) {
                $stacks[] = $stack;
            }
        }

        return $stacks;
    }

    /**
     * Create a stack model from the stack portion of a CloudFormation API
     * DescribeStacks response.
     *
     * @return Stack|null Model representing the stack, or null if the response
     *     did not have a template and environment tag.
     */
    protected function createFromApiResponse(array $response)
    {
        foreach ($response['Tags'] as $tag) {
            if ($tag['Key'] === Stack::TEMPLATE_TAG) {
                $templateName = $tag['Value'];
            } elseif ($tag['Key'] === Stack::ENVIRONMENT_TAG) {
                $environment = $tag['Value'];
            }
        }

        if (!isset($templateName) || !isset($environment)) {
            return null;
        }

        // Get the stack name from API response, for if its case has been normalised.
        $name = $response['StackName'];

        // The stack template may contain sub-stacks as URLs, expand these so
        // we have a complete representation of the stack.
        $templateExpansionService = $this->templateExpansionService;
        $cloudFormation = $this->cloudFormation;
        $templateBodyCallback = function() use ($name, $templateExpansionService, $cloudFormation) {
            return $templateExpansionService->getExpandedTemplateBody(
                $cloudFormation->GetTemplate([
                    'StackName' => $name,
                ])['TemplateBody']
            );
        };

        // LastUpdatedTime is not set if the stack has never been updated, but
        // for our purposes it is equivalent to the CreationTime in such case.
        $response['LastUpdatedTime'] = $response['CreationTime'];

        return new Stack(
            $name,
            $environment,
            new Template($templateName, $templateBodyCallback),
            Parameters::newFromCloudFormationResponseElement($response['Parameters']),
            $response['StackId'],
            $response['StackStatus'],
            new DateTime($response['CreationTime']),
            new DateTime($response['LastUpdatedTime'])
        );
    }
}
