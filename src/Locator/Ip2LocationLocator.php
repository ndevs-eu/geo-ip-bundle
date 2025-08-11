<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Locator;

use IP2Location\Database;
use NDevsEu\GeoIp\ValueObject\GeoResponse;
use NDevsEu\GeoIp\ValueObject\IpAddress;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class Ip2LocationLocator implements LocatorInterface
{
    private Database $database;

    public function __construct(
        ParameterBagInterface $parameterBag,
    ) {
        /** @var string|null $ip2LocationPath */
        $ip2LocationPath = $parameterBag->get('geo_ip.ip2location.path');

        if (!$ip2LocationPath) {
            throw new \InvalidArgumentException('IP2Location path is not configured.');
        }

        if (!is_file($ip2LocationPath.'DB.BIN')) {
            throw new \RuntimeException("IP2Location .BIN file not found: $ip2LocationPath");
        }

        $this->database = new Database($ip2LocationPath.'DB.BIN', Database::FILE_IO);
    }

    public function lookup(IpAddress $address): ?GeoResponse
    {
        try {
            /** @var array<string, string|null>|false $record */
            $record = $this->database->lookup($address->getAddress(), Database::ALL);

            if (false === $record || empty($record)) {
                return null;
            }

            return new GeoResponse(
                countryName: $record['countryName'] ?? null,
                countryIsoCode: $record['countryCode'] ?? null,
                region: null,
                city: null,
                postal: null,
                latitude: isset($record['latitude']) ? (float) $record['latitude'] : null,
                longitude: isset($record['longitude']) ? (float) $record['longitude'] : null
            );
        } catch (\Throwable $e) {
            return null;
        }
    }
}
