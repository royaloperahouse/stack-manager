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
use ROH\Bundle\StackManagerBundle\Model\Stack;

/**
 * Service to manage stacks via the CloudFormation API.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class StackManagerService
{
    /**
     * @var CloudFormation\CloudFormationClient
     */
    protected $cloudFormationClient;

    /**
     * @var TemplateSquashingService
     */
    protected $templateSquashingService;

    public function __construct(
        TemplateSquashingService $templateSquashingService,
        CloudFormation\CloudFormationClient $cloudFormationClient
    ) {
        $this->templateSquashingService = $templateSquashingService;
        $this->cloudFormationClient = $cloudFormationClient;
    }

    /**
     * Create the stack represented by the supplied model using the
     * CloudFormation API.
     *
     * @param Stack $stack Model of the stack to create.
     * @return string Id of the created stack.
     */
    public function create(Stack $stack)
    {
        $response = $this->cloudFormationClient->CreateStack([
            'Capabilities' => ['CAPABILITY_IAM'],
            'OnFailure' => 'DO_NOTHING',
            'Parameters' => $stack->getParameters()->toCloudFormationRequestArgument(),
            'StackName' => $stack->getName(),
            'TemplateURL' => $this->templateSquashingService->getSquashedTemplateURL($stack->getTemplate()),
            'Tags' => $stack->getTags()->toCloudFormationRequestArgument(),
        ]);

        return $response['StackId'];
    }

    /**
     * Update the stack represented by the supplied model using the
     * CloudFormation API.
     *
     * @param Stack $stack Model of the stack to update.
     */
    public function update(Stack $stack)
    {
        $response = $this->cloudFormationClient->UpdateStack([
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
     */
    public function delete(Stack $stack)
    {
        $response = $this->cloudFormationClient->DeleteStack([
            'StackName' => $stack->getName(),
        ]);
    }
}
