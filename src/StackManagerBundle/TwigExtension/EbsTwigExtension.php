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

use InvalidArgumentException;
use Guzzle\Service\Builder\ServiceBuilder as AwsClient;
use RuntimeException;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Twig extension to get the latest snapshot of a given EBS volume.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class EbsTwigExtension extends Twig_Extension
{
    /**
     * @var Aws\Ec2\Ec2Client
     */
    protected $ec2;

    public function __construct(AwsClient $awsClient)
    {
        $this->ec2 = $awsClient->get('ec2');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('getLatestEbsVolumeSnapshot', [$this, 'getLatestEbsVolumeSnapshot']),
        ];
    }

    /**
     * Return the latest completed snapshot for a given EBS volume id.
     *
     * @param string $volumeId Id of the EBS volume to get the latest completed
     *     snapshot id for.
     * @return string Latest snapshot id of the specified EBS volume.
     */
    public function getLatestEbsVolumeSnapshot($volumeId)
    {
        if (!strlen($volumeId)) {
            throw new InvalidArgumentException(sprintf(
                'Volume id passed to "%s" must be a non-zero length string',
                __FUNCTION__
            ));
        }

        $response = $this->ec2->describeSnapshots([
            'Filters' => [
                [
                    'Name' => 'volume-id',
                    'Values' => [$volumeId],
                ],
                [
                    'Name' => 'status',
                    'Values' => ['completed'],
                ],
            ],
        ]);

        $snapshots = $response['Snapshots'];
        if (!$snapshots) {
            throw new RuntimeException(sprintf(
                'No snapshots returned for volume id "%s" by the EC2 API',
                $volumeId
            ));
        }

        return end($snapshots)['SnapshotId'];
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'roh_ebs';
    }
}
