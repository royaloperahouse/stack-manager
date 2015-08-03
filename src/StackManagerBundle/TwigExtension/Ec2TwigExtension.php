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
use Guzzle;
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
     * @var Aws\Ec2\Ec2Client
     */
    protected $ec2;

    /**
     * @param Guzzle\Service\Builder\ServiceBuilder $awsClient AWS client.
     */
    public function __construct(Guzzle\Service\Builder\ServiceBuilder $awsClient)
    {
        $this->ec2 = $awsClient->get('ec2');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
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
    public function getLatestEc2ImageByOwnerAndDescription($ownerAlias, $description)
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

        $response = $this->ec2->describeImages([
            'Filters' => [
                [
                    'Name' => 'owner-alias',
                    'Values' => [$ownerAlias],
                ],
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
    public function getName()
    {
        return 'roh_ec2';
    }

    /**
     * Take a DescribeImages API response and find the latest available image
     * from the returned data.
     *
     * @throws RuntimeException If no available images are found in the response.
     * @param Guzzle\Service\Resource\Model $response Response from the
     *     DescribeImages API call.
     * @return string Latest image id.
     */
    private function getLatestEc2ImageFromResponse(Guzzle\Service\Resource\Model $response)
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

        return end(array_keys($images));
    }
}
