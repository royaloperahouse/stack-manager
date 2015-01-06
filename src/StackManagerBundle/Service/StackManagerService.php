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
use ROH\Bundle\StackManagerBundle\Model\Stack;
use ROH\Bundle\StackManagerBundle\Service\TemplateSquashingService;

/**
 * Service to manage stacks via the CloudFormation API.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class StackManagerService
{
    protected $cloudFormation;

    protected $templateSquashingService;

    public function __construct(
        TemplateSquashingService $templateSquashingService,
        AwsClient $awsClient
    ) {
        $this->templateSquashingService = $templateSquashingService;
        $this->cloudFormation = $awsClient->get('CloudFormation');
    }

    /**
     * Create the stack represented by the supplied model using the CloudFormation API.
     *
     * @param Stack $stack Model of the stack to create.
     * @return string Id of the created stack.
     */
    public function create(Stack $stack)
    {
        $response = $this->cloudFormation->CreateStack([
            'Capabilities' => ['CAPABILITY_IAM'],
            'OnFaliure' => 'DO_NOTHING',
            'Parameters' => $stack->getParameters()->toCloudFormationRequestArgument(),
            'StackName' => $stack->getName(),
            'TemplateURL' => $this->templateSquashingService->getSquashedTemplateURL($stack->getTemplate()),
            'Tags' => $stack->getTags()->toCloudFormationRequestArgument(),
        ]);

        return $response['StackId'];
    }

    /**
     * Update the stack represented by the supplied model using the CloudFormation API.
     *
     * @param Stack $stack Model of the stack to update.
     * @return void
     */
    public function update(Stack $stack)
    {
        $response = $this->cloudFormation->UpdateStack([
            'Capabilities' => ['CAPABILITY_IAM'],
            'StackName' => $stack->getName(),
            'Parameters' => $stack->getParameters()->toCloudFormationRequestArgument(),
            'TemplateURL' => $this->templateSquashingService->getSquashedTemplateURL($stack->getTemplate()),
        ]);
    }

    /**
     * Delete the stack represented by the supplied model using the CloudFormation API.
     *
     * @param Stack $stack Model of the stack to delete.
     * @return void
     */
    public function delete(Stack $stack)
    {
        $response = $this->cloudFormation->DeleteStack([
            'StackName' => $stack->getName(),
        ]);
    }
}
