<?php

namespace ROH\Bundle\StackManagerBundle\Tests\TwigExtension;

use PHPUnit_Framework_TestCase;
use ROH\Bundle\StackManagerBundle\TwigExtension\CidrTwigExtension;

class CidrTwigExtensionTest extends PHPUnit_Framework_TestCase
{
    public function testBasicUsage()
    {
        $ext = new CidrTwigExtension;
        $this->assertEquals(
            '10.0.0.0/16',
            $ext->cidr('10.0.0.0/16', 16)
        );
    }

    public function testContainerBlockSizeExceeded()
    {
        $this->setExpectedExceptionRegExp(
          'Exception', '/The size of the desired block exceeds the size of the container block.*/'
        );

        $ext = new CidrTwigExtension;
        $ext->cidr('10.0.0.0/24', 16);
    }

    public function testIncrementingPrefixSizes()
    {
        $results = [
            1 => '0.0.0.0/1',
            2 => '128.0.0.0/2',
            3 => '192.0.0.0/3',
            4 => '224.0.0.0/4',
            5 => '240.0.0.0/5',
            6 => '248.0.0.0/6',
            7 => '252.0.0.0/7',
            8 => '254.0.0.0/8',
            9 => '255.0.0.0/9',
            10 => '255.128.0.0/10',
            11 => '255.192.0.0/11',
            12 => '255.224.0.0/12',
            13 => '255.240.0.0/13',
            14 => '255.248.0.0/14',
            15 => '255.252.0.0/15',
            16 => '255.254.0.0/16',
            17 => '255.255.0.0/17',
            18 => '255.255.128.0/18',
            19 => '255.255.192.0/19',
            20 => '255.255.224.0/20',
            21 => '255.255.240.0/21',
            22 => '255.255.248.0/22',
            23 => '255.255.252.0/23',
            24 => '255.255.254.0/24',
            25 => '255.255.255.0/25',
            26 => '255.255.255.128/26',
            27 => '255.255.255.192/27',
            28 => '255.255.255.224/28',
            29 => '255.255.255.240/29',
            30 => '255.255.255.248/30',
            31 => '255.255.255.252/31',
            32 => '255.255.255.254/32',
        ];

        $ext = new CidrTwigExtension;
        foreach ($results as $prefixSize => $cidr) {
            $this->assertEquals(
                $cidr,
                $ext->cidr('0.0.0.0/0', $prefixSize)
            );
        }
    }
}
