<?php

namespace App\Helpers;

class PlatformHelper
{
    /**
     * Collection of platforms keyed by slug.
     *
     * @var array
     */
    protected $platforms = [
        'alibaba'    => 'Alibaba',
        'aliexpress' => 'Aliexpress',
        'dhgate'     => 'DHGate',
        'ebay'       => 'eBay',
        'wish'       => 'Wish',
        'lelong'       => 'LeLong',
        'taobao'       => 'Taobao',
        'made-in-china'       => 'Made-in-China',
        'bukalapak'       => 'Bukalapak',
        'amazon'     => 'Amazon',
        '1688'       => '1688',
        'lazada'       => 'Lazada',
        'everychina'       => 'Everychina',
        'diytrade'       => 'DIY Trade',
        'fasttech'   => 'FastTech',
        'instagram'       => 'Instagram',
        'website'       => 'Website',
    ];

    /**
     * Return a list of platforms keyed by slug.
     *
     * @return array
     */
    public function getPlatforms()
    {
        return $this->platforms;
    }

    /**
     * Return a list of platform slugs.
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->platforms);
    }
}
