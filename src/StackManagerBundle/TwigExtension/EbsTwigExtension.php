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

use Aws\Ec2;
use InvalidArgumentException;
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
     * @var Ec2\Ec2Client
     */
    protected $ec2Client;

    /**
     * @param Ec2\Ec2Client $ec2Client AWS client.
     */
    public function __construct(Ec2\Ec2Client $ec2Client)
    {
        $this->ec2Client = $ec2Client;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('getLatestEbsVolumeSnapshot', [$this, 'getLatestEbsVolumeSnapshot']),
            new Twig_SimpleFunction('getEbsVolumeSourceSnapshot', [$this, 'getEbsVolumeSourceSnapshot']),
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

        $response = $this->ec2Client->describeSnapshots([
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

        return $this->getLatestEbsVolumeSnapshotFromResponse($response);
    }

    /**
     * Get the source snapshot for a given EBS volume id.
     *
     * @param string $volumeId Id of EBS volume to get the source snapshot of.
     * @return string Source snapshto id of the specified EBS volume.
     */
    public function getEbsVolumeSourceSnapshot($volumeId)
    {
        if (!strlen($volumeId)) {
            throw new InvalidArgumentException(sprintf(
                'Volume id passed to "%s" must be a non-zero length string',
                __FUNCTION__
            ));
        }

        $response = $this->ec2Client->describeVolumes([
            'VolumeIds' => [$volumeId],
        ]);

        if (!count($response['Volumes']) || !$response['Volumes'][0]['SnapshotId']) {
            return null;
        }

        return $response['Volumes'][0]['SnapshotId'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'roh_ebs';
    }

    /**
     * Take a DescribeSnapshots API response and find the latest snapshot from
     * the returned data.
     *
     * @throws RuntimeException If no completed snapshots are found in the
     *     response.
     * @param Guzzle\Service\Resource\Model $response Response from the
     *     DescribeSnapshots API call.
     * @return string Latest snapshot id.
     */
    private function getLatestEbsVolumeSnapshotFromResponse(Guzzle\Service\Resource\Model $response)
    {
        if (!$response['Snapshots']) {
            throw new RuntimeException('No snapshots returned in the EC2 API response');
        }

        $snapshots = [];
        foreach ($response['Snapshots'] as $snapshot) {
            if ($snapshot['State'] !== 'completed') {
                continue;
            }

            $snapshots[$snapshot['SnapshotId']] = strtotime($snapshot['StartTime']);
        }
        asort($snapshots);

        if (count($snapshots) === 0) {
            throw new RuntimeException('No completed snapshots returned in the the EC2 API');
        }

        end($snapshots);
        return key($snapshots);
    }
}
