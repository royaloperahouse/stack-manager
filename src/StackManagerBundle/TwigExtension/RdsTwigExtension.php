<?php

/*
 * This file is part of the Stack Manager package.
 *
 * (c) Royal Opera House Covent Garden Foundation <website@roh.org.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ROH\Bundle\StackManagerBundle\TwigExtension;

use Aws\Rds;
use InvalidArgumentException;
use Guzzle\Service\Builder\ServiceBuilder as AwsClient;
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
     * @var Aws\Rds\RdsClient
     */
    protected $rds;

    /**
     * @var Aws\Iam\IamClient
     */
    protected $iam;

    /**
     * @var string
     */
    protected $region;

    public function __construct(AwsClient $awsClient, $awsRegion)
    {
        $this->rds = $awsClient->get('rds');
        // The IAM API is only available in us-east-1.
        $this->iam = $awsClient->get('iam', ['region' => 'us-east-1']);
        $this->region = $awsRegion;
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
     * Return the latest automated DB snapshot for a given RDS DB instance
     * identifier.
     *
     * @param string $instanceIdentifier Identifier of the DB instance to get
     *     the snapshot identifier for.
     * @return string Latest automated snapshot identifier of the specified DB
     *     instance.
     */
    public function getLatestRdsSnapshot($instanceIdentifier)
    {
        if (!strlen($instanceIdentifier)) {
            throw new InvalidArgumentException(sprintf(
                'Instance identifier passed to "%s" must be a non-zero length string',
                __FUNCTION__
            ));
        }

        $response = $this->rds->describeDBSnapshots([
            'DBInstanceIdentifier' => $instanceIdentifier,
            'SnapshotType' => 'automated',
        ]);

        $snapshots = $response['DBSnapshots'];
        if (!$snapshots) {
            throw new RuntimeException(sprintf(
                'No snapshots returned for DB instance "%s" by the RDS API',
                $instanceIdentifier
            ));
        }

        return end($snapshots)['DBSnapshotIdentifier'];
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
        $accountId = explode(':', $this->iam->GetUser()['User']['Arn'])[4];
        $arn = sprintf(
            'arn:aws:rds:%s:%d:db:%s',
            $this->region,
            $accountId,
            $instanceIdentifier
        );

        try {
            $tags = $this->rds->ListTagsForResource(['ResourceName' => $arn])['TagList'];
        } catch (Rds\Exception\DBInstanceNotFoundException $e) {
            return null;
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
}
