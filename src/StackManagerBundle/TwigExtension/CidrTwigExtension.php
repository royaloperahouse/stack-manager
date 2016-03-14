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

use Exception;
use Twig_Extension;
use Twig_SimpleFilter;

/**
 * Provides a mechanism for finding the next available subnet of a given size
 * within a given CIDR block.
 *
 * For a given CIDR block, this extension maintains a record of the last IP
 * address that was given out for that block, and when another block is asked
 * for returns the next available block.
 *
 * @author Robert Leverington <robert.leverington@roh.org.uk>
 */
class CidrTwigExtension extends Twig_Extension
{
    /**
     * An array of container blocks, and the minimum IP address that desired
     * blocks may be generated from them.  E.g. ['172.31.1.0/16' => 2887713040].
     *
     * @var array
     */
    protected $minimumIpForBlock = [];

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new Twig_SimpleFilter('cidr', array($this, 'cidr')),
        ];
    }

    /**
     * Takes a container CIDR block, and generates a unique block from within
     * that based on the desired block prefix size.
     *
     * @param string $containerBlock
     */
    public function cidr(string $containerBlock, int $desiredBlockPrefixSize): string
    {
        // Translate the container block from CIDR to a prefix and netmask.
        list($containerBlockPrefix, $containerBlockPrefixSize) = explode('/', $containerBlock);
        $containerBlockPrefix = ip2long($containerBlockPrefix);
        $containerBlockNetmask = $this->prefixSizeToNetmask($containerBlockPrefixSize);

        // Normalise the container block (e.g. 172.31.0.5/24 becomes 172.31.0.0/24).
        $containerBlockPrefix = $this->getPrefix($containerBlockPrefix, $containerBlockNetmask);
        $containerBlock = long2ip($containerBlockPrefix).'/'.$containerBlockPrefixSize;

        // If the specified container block has not been used before, record
        // the minimum IP address of it as the prefix of the container block.
        if (!isset($this->minimumIpInContainerBlock[$containerBlock])) {
            $this->minimumIpInContainerBlock[$containerBlock] = $containerBlockPrefix;
        }

        $desiredBlockNetmask = $this->prefixSizeToNetmask($desiredBlockPrefixSize);
        $desiredBlockPrefix = $this->getPrefix($this->minimumIpInContainerBlock[$containerBlock], $desiredBlockNetmask);
        $desiredBlockBroadcast = $this->getBroadcast($desiredBlockPrefix, $desiredBlockNetmask);
        $containerBlockBroadcast = $this->getBroadcast($containerBlockPrefix, $containerBlockNetmask);
        if ($desiredBlockBroadcast > $containerBlockBroadcast) {
            throw new Exception(sprintf(
                "The size of the desired block exceeds the size of the container block.\n"
                    ."  Container CIDR: %s/%d (%s -> %s)\n"
                    .'  Desired CIDR: %s/%d (%s -> %s)',
                long2ip($containerBlockPrefix),
                $containerBlockPrefixSize,
                long2ip($containerBlockPrefix),
                long2ip($containerBlockBroadcast),
                long2ip($desiredBlockPrefix),
                $desiredBlockPrefixSize,
                long2ip($desiredBlockPrefix),
                long2ip($desiredBlockBroadcast)
            ));
        }

        // The new minimum IP address of this container CIDR block is now the
        // broadcast address of the desired block, plus one.
        $this->minimumIpInContainerBlock[$containerBlock] = $desiredBlockBroadcast + 1;

        return long2ip($desiredBlockPrefix).'/'.$desiredBlockPrefixSize;
    }

    /**
     * Convert a prefix size (e.g. 16) to a subnet mask (e.g. 0xFFFF0000).
     *
     * @param int $prefixSize
     * @return int
     */
    private function prefixSizeToNetmask(int $prefixSize): int
    {
        return ~(pow(2, 32 - $prefixSize) - 1) & 0xFFFFFFFF;
    }

    /**
     * For a given IP address and netmask, get the prefix of that range (the
     * first IP address).
     *
     * @param int $ip
     * @param int $netmask
     * @return int
     */
    private function getPrefix(int $ip, int $netmask): int
    {
        return $ip & $netmask;
    }

    /**
     * For a given IP address and netmask, get the broadcast address of that
     * range (the last IP address).
     *
     * @param int $ip
     * @param int $netmask
     * @return int
     */
    private function getBroadcast(int $ip, int $netmask): int
    {
        return $ip | ~$netmask & 0xFFFFFFFF;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'roh_cidr';
    }
}
