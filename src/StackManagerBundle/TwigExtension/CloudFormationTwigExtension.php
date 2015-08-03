<?php

/*
 * This file is part of the Stack Manager package.
 *
 * © Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\TwigExtension;

use Aws\CloudFormation;
use InvalidArgumentException;
use Guzzle;
use RuntimeException;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Twig extension to get the physical resource ids of CloudFormation resources.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class CloudFormationTwigExtension extends Twig_Extension
{
    /**
     * @var CloudFormation\CloudFormationClient
     */
    protected $cloudFormation;

    /**
     * @param Guzzle\Service\Builder\ServiceBuilder $awsClient AWS client.
     */
    public function __construct(
        Guzzle\Service\Builder\ServiceBuilder $awsClient
    ) {
        $this->cloudFormation = $awsClient->get('cloudformation');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('getCloudFormationPhysicalResourceIdByResourceName', [$this, 'getCloudFormationPhysicalResourceIdByResourceName']),
        ];
    }

    /**
     * Return the physical resource id of a resource specified in a CloudFormation
     * stack.
     *
     * @param string $stackName Name or id of the stack.
     * @param string $resourceName Name of resource as specified in the
     *     template.
     * @return string Phyiscal resource id.
     */
    public function getCloudFormationPhysicalResourceIdByResourceName(
        $stackName,
        $resourceName
    ) {
        if (!strlen($stackName)) {
            throw new InvalidArgumentException(sprintf(
                'Stack name passed to "%s" must be a non-zero length string',
                __FUNCTION__
            ));
        }
        if (!strlen($resourceName)) {
            throw new InvalidArgumentException(sprintf(
                'Resource name passed to "%s" must be a non-zero length string',
                __FUNCTION__
            ));
        }

        try {
            $response = $this->cloudFormation->describeStackResource([
                'StackName' => $stackName,
                'LogicalResourceId' => $resourceName,
            ]);
        } catch (CloudFormation\Exception\CloudFormationException $e) {
            if (preg_match('#Stack \'.*?\' does not exist#', $e->getMessage())) {
                return null;
            }
            throw new $e;
        }

        if (!isset($response['StackResourceDetail'])) {
            throw new RuntimeException(sprintf(
                'No resource with name "%s" found in stack "%s".',
                $resourceName,
                $stackName
            ));
        }

        return $response['StackResourceDetail']['PhysicalResourceId'];
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'roh_cloudformation';
    }
}
