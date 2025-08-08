<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Service;

use NDevsEu\GeoIp\Locator\FallbackLocator;
use NDevsEu\GeoIp\ValueObject\GeoResponse;
use NDevsEu\GeoIp\ValueObject\IpAddress;

readonly class GeoIpService
{
    public function __construct(
        private FallbackLocator $fallbackLocator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function lookup(IpAddress $ip): ?GeoResponse
    {
        if ($ip->isPrivate()) {
            throw new \InvalidArgumentException('Private IP addresses are not supported.');
        }

        return $this->fallbackLocator->lookup($ip);
    }
}
