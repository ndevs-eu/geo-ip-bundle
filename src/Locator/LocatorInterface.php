<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Locator;

use NDevsEu\GeoIp\ValueObject\GeoResponse;
use NDevsEu\GeoIp\ValueObject\IpAddress;

interface LocatorInterface
{
    public function lookup(IpAddress $address): ?GeoResponse;
}
