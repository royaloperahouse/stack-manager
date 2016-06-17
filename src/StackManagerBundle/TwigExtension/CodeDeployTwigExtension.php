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

use Aws;
use Aws\CodeDeploy;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Twig extension to get the latest revision of a given CodeDeploy application.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class CodeDeployTwigExtension extends Twig_Extension
{
    /**
     * @var CodeDeploy\CodeDeployClient
     */
    protected $codeDeployClient;

    /**
     * @param CodeDeploy\CodeDeployClient $codeDeployClient CodeDeploy client
     */
    public function __construct(CodeDeploy\CodeDeployClient $codeDeployClient) {
        $this->codeDeployClient = $codeDeployClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('getLatestCodeDeployApplicationRevision', [$this, 'getLatestCodeDeployApplicationRevision']),
            new Twig_SimpleFunction('getLatestCodeDeployDeploymentGroupRevision', [$this, 'getLatestCodeDeployDeploymentGroupRevision']),
        ];
    }

    /**
     * Get information about the latest CodeDeploy revision of the specified
     * application.
     *
     * @param string $applicationName Name of application to get the latest
     *     revision of.
     * @return string JSON representation of the latest application revision.
     */
    public function getLatestCodeDeployApplicationRevision($applicationName)
    {
        $response = $this->codeDeployClient->ListApplicationRevisions([
            'applicationName' => $applicationName
        ]);
        $revision = end($response['revisions']);

        return $this->convertCodeDeployRevisionToCloudFormationRevision($revision);
    }


    /**
     * Get information about the latest CodeDeploy revision of the specified
     * deployment group.
     *
     * @param string $applicationName Name of application used by the deployment
     *     group.
     * @param string $deploymentGroup Name of deployment group to get the latest
     *     revision of.
     * @return string JSON representation of the latest deployment group
     *     revision.
     */
    public function getLatestCodeDeployDeploymentGroupRevision($applicationName, $deploymentGroupName)
    {
        try {
            $response = $this->codeDeployClient->GetDeploymentGroup([
                'applicationName' => $applicationName,
                'deploymentGroupName' => $deploymentGroupName,
            ]);
        } catch (CodeDeploy\Exception\CodeDeployException $e) {
            $error = json_decode($e->getResponse()->getBody(), true);
            if ($error['__type'] === 'DeploymentGroupDoesNotExistException') {
                return null;
            }

            throw $e;
        }

        if (!$response) {
            return null;
        }

        $revision = $response['deploymentGroupInfo']['targetRevision'];

        return $this->convertCodeDeployRevisionToCloudFormationRevision($revision);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'roh_codedeploy';
    }

    private function convertCodeDeployRevisionToCloudFormationRevision(array $revision)
    {
        /**
         * CodeDeploy returns it keys with a lower case first letter, but
         * CloudFormation expects them with an upper case first letter (like
         * most other AWS APIs).  Transform the data returned.
         */
        $ucfirstArray = function ($array) use (&$ucfirstArray) {
            $filtered = [];
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $value = $ucfirstArray($value);
                }
                $filtered[ucfirst($key)] = $value;
            }
            return $filtered;
        };

        return json_encode($ucfirstArray($revision));
    }
}
