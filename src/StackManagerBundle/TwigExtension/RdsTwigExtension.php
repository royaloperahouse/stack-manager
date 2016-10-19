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
use Aws\Rds;
use Aws\Iam;
use InvalidArgumentException;
use RuntimeException;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Twig extension to get the latest automated snapshot of a given RDS instance.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class RdsTwigExtension extends Twig_Extension
{
    /**
     * @var Rds\RdsClient
     */
    protected $rdsClient;

    /**
     * @var Iam\IamClient
     */
    protected $iamClient;

    /**
     * @param Rds\RdsClient $rdsClient RDS client.
     * @param Iam\IamClient $iamClient IAM client.
     */
    public function __construct(Rds\RdsClient $rdsClient, Iam\IamClient $iamClient) {
        $this->rdsClient = $rdsClient;
        $this->iamClient = $iamClient;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('getLatestRdsSnapshot', [$this, 'getLatestRdsSnapshot']),
            new Twig_SimpleFunction('getRdsDbInstanceTag', [$this, 'getRdsDbInstanceTag']),
        ];
    }

    /**
     * Return the latest available DB snapshot for a given RDS DB instance
     * identifier.
     *
     * @param string $instanceIdentifier Identifier of the DB instance to get
     *     the snapshot identifier for.
     * @return string Latest snapshot identifier of the specified DB instance.
     */
    public function getLatestRdsSnapshot($instanceIdentifier)
    {
        if (!strlen($instanceIdentifier)) {
            throw new InvalidArgumentException(sprintf(
                'Instance identifier passed to "%s" must be a non-zero length string',
                __FUNCTION__
            ));
        }

        $response = $this->rdsClient->describeDBSnapshots([
            'IncludeShared' => true,
        ]);

        $matchedResponse = clone $response;
        $matchedResponse['DBSnapshots'] = [];
        foreach ($response['DBSnapshots'] as &$snapshot) {
            if ($snapshot['DBInstanceIdentifier'] === $instanceIdentifier) {
                $matchedResponse['DBSnapshots'][] = $snapshot;
            }
        }

        return $this->getLatestRdsSnapshotFromResponse($matchedResponse);
    }

    /**
     * Get the value of a tag for a specified DB instance.
     *
     * @param string $instanceIdentifier Identifier of the DB instance to get
     *     the tag of.
     * @param string $tagKey Key of tag to get.
     * @return string|null Value of tag or null if the db instance or tag does
     *     not exist.
     */
    public function getRdsDbInstanceTag($instanceIdentifier, $tagKey)
    {
        // Compose the ARN of the DB instance.
        $accountId = explode(':', $this->iamClient->GetUser()['User']['Arn'])[4];
        $arn = sprintf(
            'arn:aws:rds:%s:%s:db:%s',
            $this->rdsClient->getRegion(),
            $accountId,
            $instanceIdentifier
        );

        try {
            $tags = $this->rdsClient->ListTagsForResource(['ResourceName' => $arn])['TagList'];
        } catch (Rds\Exception\RdsException $e) {
            if (strpos($e->getMessage(), 'DBInstanceNotFound') !== false) {
                return null;
            }

            throw $e;
        }

        foreach ($tags as $tag) {
            if ($tag['Key'] === $tagKey) {
                return $tag['Value'];
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'roh_rds';
    }

    /**
     * Take a DescribeDBSnapshots API response and find the latest snapshot from
     * the returned data.
     *
     * @throws RuntimeException If no available snapshtos are found in the
     *     response.
     * @param Aws\Result $response Response from the DescribeDBSnapshots API
     *     call.
     * @return string Latest snapshot id.
     */
    private function getLatestRdsSnapshotFromResponse(Aws\Result $response)
    {
        if (!$response['DBSnapshots']) {
            throw new RuntimeException('No snapshots returned in the RDS API response');
        }

        $snapshots = [];
        foreach ($response['DBSnapshots'] as $snapshot) {
            if ($snapshot['Status'] !== 'available') {
                continue;
            }

            $snapshots[$snapshot['DBSnapshotIdentifier']] = strtotime($snapshot['SnapshotCreateTime']);
        }
        asort($snapshots);

        if (count($snapshots) === 0) {
            throw new RuntimeException('No available snapshots returned in the RDS API response');
        }

        end($snapshots);
        return key($snapshots);
    }
}
