<?php

namespace Jez500\WebScraperForLaravel;

class UserAgentGenerator
{
    protected array $platforms = [
        'Windows NT 10.0; Win64; x64',
        'Windows NT 6.1; Win64; x64',
        'Macintosh; Intel Mac OS X 10_13_2',
        'Macintosh; Intel Mac OS X 10_12_6',
        'X11; Linux x86_64',
        'X11; Fedora; Linux x86_64',
    ];

    protected array $browsers = [
        'Chrome' => [
            'engine' => 'AppleWebKit',
            'versions' => ['62.0.3202.94', '63.0.3239.84', '61.0.3163.100'],
        ],
        'Firefox' => [
            'engine' => 'Gecko',
            'versions' => ['57.0', '58.0', '52.0'],
        ],
        'Safari' => [
            'engine' => 'AppleWebKit',
            'versions' => ['604.4.7', '604.3.5', '603.3.8'],
        ],
        'Edge' => [
            'engine' => 'EdgeHTML',
            'versions' => ['16.16299', '15.15063', '14.14393'],
        ],
        'Opera' => [
            'engine' => 'AppleWebKit',
            'versions' => ['49.0.2725.47', '49.0.2725.64'],
        ],
        'IE' => [
            'engine' => 'Trident',
            'versions' => ['11.0', '10.0'],
        ],
    ];

    public function generate(): string
    {
        $platform = $this->platforms[array_rand($this->platforms)];
        $browser = array_rand($this->browsers);
        $browserDetails = $this->browsers[$browser];

        $engine = $browserDetails['engine'];
        $browserVersion = $browserDetails['versions'][array_rand($browserDetails['versions'])];

        $userAgent = "Mozilla/5.0 ({$platform}) {$engine}/537.36 (KHTML, like Gecko) ";

        if ($browser === 'Chrome') {
            $userAgent .= "Chrome/{$browserVersion} Safari/537.36";
        } elseif ($browser === 'Firefox') {
            $userAgent .= "rv:{$browserVersion} Gecko/20100101 Firefox/{$browserVersion}";
        } elseif ($browser === 'Safari') {
            $userAgent .= "Version/11.0 Safari/{$browserVersion}";
        } elseif ($browser === 'Edge') {
            $userAgent .= "Chrome/{$browserVersion} Safari/537.36 Edge/{$browserVersion}";
        } elseif ($browser === 'Opera') {
            $userAgent .= "Chrome/{$browserVersion} Safari/537.36 OPR/{$browserVersion}";
        } elseif ($browser === 'IE') {
            $userAgent = "Mozilla/5.0 ({$platform}; Trident/7.0; rv:{$browserVersion}) like Gecko";
        }

        return $userAgent;
    }
}
