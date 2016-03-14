<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\Mapper;

use Aws\CloudFormation;
use DateTimeImmutable;
use RuntimeException;
use ROH\Bundle\StackManagerBundle\Exception\StackNotHandledException;
use ROH\Bundle\StackManagerBundle\Model\Parameters;
use ROH\Bundle\StackManagerBundle\Model\ApiStack;
use ROH\Bundle\StackManagerBundle\Model\Template;
use ROH\Bundle\StackManagerBundle\Service\TemplateExpansionService;

/**
 * Mapper to create stack models from the CloudFormation API.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class StackApiMapper
{
    /**
     * @var CloudFormation\CloudFormationClient
     */
    protected $cloudFormationClient;

    /**
     * @var TemplateExpansionService
     */
    protected $templateExpansionService;

    public function __construct(
        TemplateExpansionService $templateExpansionService,
        CloudFormation\CloudFormationClient $cloudFormationClient
    ) {
        $this->templateExpansionService = $templateExpansionService;
        $this->cloudFormationClient = $cloudFormationClient;
    }

    /**
     * Create a Stack model from the specified stack using the CloudFormation API.
     *
     * @param string $name Name of stack to create the stack model from.
     * @return ApiStack Model representing the stack.
     */
    public function create(string $name): ApiStack
    {
        $response = $this->cloudFormationClient->DescribeStacks([
            'StackName' => $name,
        ]);
        $stack = $this->createFromApiResponse(current($response->get('Stacks')));

        return $stack;
    }

    /**
     * Get Stack models for all stacks with an appropriate template and
     * environment tag using the CloudFormation API.
     *
     * @return ApiStack[] Stack models
     */
    public function findAll(): array
    {
        $stacks = [];

        $response = $this->cloudFormationClient->DescribeStacks();
        foreach ($response['Stacks'] as $data) {
            try {
                $stack = $this->createFromApiResponse($data);
            } catch (StackNotHandledException $e) {
            }

            if ($stack && !$stack->isChildStack()) {
                $stacks[$stack->getName()] = $stack;
            }
        }
        ksort($stacks);

        return array_values($stacks);
    }

    /**
     * Create a stack model from the stack portion of a CloudFormation API
     * DescribeStacks response.
     *
     * @throws RuntimeException If the stack is missing a template or
     *     environment tag.
     * @return ApiStack Model representing the stack.
     */
    protected function createFromApiResponse(array $response): ApiStack
    {
        assert(isset($response['StackId']), 'Stack portion of CloudFormation API response must contain a "StackId" key');
        assert(isset($response['StackName']), 'Stack portion of CloudFormation API response must contain a "StackName" key');
        assert(isset($response['StackStatus']), 'Stack portion of CloudFormation API response must contain a "StackStatus" key');
        assert(isset($response['Parameters']), 'Stack portion of CloudFormation API response must contain a "Parameters" key');
        assert(isset($response['Tags']), 'Stack portion of CloudFormation API response must contain a "Tags" key');
        assert(isset($response['CreationTime']), 'Stack portion of CloudFormation API response must contain a "CreationTime" key');

        foreach ($response['Tags'] as $tag) {
            assert(isset($tag['Key']), 'Tags portion of CloudFormation API response must contain a "Key" key');
            assert(isset($tag['Value']), 'Tags portion of CloudFormation API response must contain a "Value" key');

            if ($tag['Key'] === ApiStack::TEMPLATE_TAG) {
                $templateName = $tag['Value'];
            } elseif ($tag['Key'] === ApiStack::ENVIRONMENT_TAG) {
                $environment = $tag['Value'];
            }
        }

        if (!isset($templateName)) {
            throw new StackNotHandledException(sprintf(
                'Tag "%s" for template missing from stack "%s"',
                ApiStack::TEMPLATE_TAG,
                $response['StackName']
            ));
        } elseif (!isset($environment)) {
            throw new StackNotHandledException(sprintf(
                'Tag "%s" for environment missing from stack "%s"',
                ApiStack::ENVIRONMENT_TAG,
                $response['StackName']
            ));
        }

        // Get the stack name from API response, for if its case has been normalised.
        $name = $response['StackName'];

        // The stack template may contain sub-stacks as URLs, expand these so
        // we have a complete representation of the stack.
        $templateExpansionService = $this->templateExpansionService;
        $cloudFormationClient = $this->cloudFormationClient;
        $templateBodyCallback = function () use (
            $name,
            $templateExpansionService,
            $cloudFormationClient
        ) {
            return $templateExpansionService->getExpandedTemplateBody(
                $cloudFormationClient->GetTemplate([
                    'StackName' => $name,
                ])['TemplateBody']
            );
        };

        // LastUpdatedTime is not set if the stack has never been updated, but
        // for our purposes it is equivalent to the CreationTime in such case.
        if (!isset($response['LastUpdatedTime'])) {
            $response['LastUpdatedTime'] = $response['CreationTime'];
        }

        return new ApiStack(
            $name,
            $environment,
            new Template($templateName, $templateBodyCallback),
            Parameters::newFromCloudFormationResponseElement($response['Parameters']),
            $response['StackId'],
            $response['StackStatus'],
            new DateTimeImmutable($response['CreationTime']),
            new DateTimeImmutable($response['LastUpdatedTime'])
        );
    }
}
