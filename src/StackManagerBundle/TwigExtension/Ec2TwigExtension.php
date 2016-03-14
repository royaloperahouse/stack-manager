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
use Aws\Ec2;
use InvalidArgumentException;
use RuntimeException;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Twig extension to get the latest EC2 image with a given owner and description.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class Ec2TwigExtension extends Twig_Extension
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
    public function getFunctions(): array
    {
        return [
            new Twig_SimpleFunction(
                'getLatestEc2ImageByOwnerAndDescription',
                [$this, 'getLatestEc2ImageByOwnerAndDescription']
            ),
        ];
    }

    /**
     * Return the latest EC2 image with the specified owner and description.
     *
     * @param string $ownerAlias Alias of the image owner.
     * @param string $description Description of the image.
     * @return string Latest image id with the specified owner and description.
     */
    public function getLatestEc2ImageByOwnerAndDescription(string $ownerAlias, string $description): string
    {
        if (!strlen($ownerAlias)) {
            throw new InvalidArgumentException(sprintf(
                'Owner alias passed to "%s" must be a non-zero length string',
                __FUNCTION__
            ));
        }
        if (!strlen($description)) {
            throw new InvalidArgumentException(sprintf(
                'Description passed to "%s" must be a non-zero length string',
                __FUNCTION__
            ));
        }

        $response = $this->ec2Client->describeImages([
            'Owners' => [$ownerAlias],
            'Filters' => [
                [
                    'Name' => 'description',
                    'Values' => [$description],
                ],
            ],
        ]);

        return $this->getLatestEc2ImageFromResponse($response);
    }


    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'roh_ec2';
    }

    /**
     * Take a DescribeImages API response and find the latest available image
     * from the returned data.
     *
     * @throws RuntimeException If no available images are found in the response.
     * @param Aws\Result $response Response from the DescribeImages API call.
     * @return string Latest image id.
     */
    private function getLatestEc2ImageFromResponse(Aws\Result $response): string
    {
        if (!$response['Images']) {
            throw new RuntimeException('No images returned in the EC2 API response');
        }

        $images = [];
        foreach ($response['Images'] as $image) {
            if ($image['State'] !== 'available') {
                continue;
            }

            $images[$image['ImageId']] = strtotime($image['CreationDate']);
        }
        asort($images);

        if (count($images) === 0) {
            throw new RuntimeException('No available images returned in the EC2 API response');
        }

        end($images);
        return key($images);
    }
}
