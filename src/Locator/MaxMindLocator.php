<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Locator;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use NDevsEu\GeoIp\ValueObject\GeoResponse;
use NDevsEu\GeoIp\ValueObject\IpAddress;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MaxMindLocator implements LocatorInterface
{
    private Reader $maxMindDbReader;

    public function __construct(
        ParameterBagInterface $parameterBag,
    ) {
        /** @var string|null $maxMindDbPath */
        $maxMindDbPath = $parameterBag->get('geo_ip.maxmind.path');

        if (!$maxMindDbPath) {
            throw new \InvalidArgumentException('MaxMind database path is not configured.');
        }

        try {
            $this->maxMindDbReader = new Reader(filename: $maxMindDbPath.'/GeoIp.mmdb');
        } catch (InvalidDatabaseException $e) {
            throw new \RuntimeException('Invalid MaxMind database file: '.$maxMindDbPath, 0, $e);
        }
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function lookup(IpAddress $address): ?GeoResponse
    {
        $record = $this->maxMindDbReader->city($address->getAddress());

        return new GeoResponse(
            country: $record->country->isoCode ?? null,
            region: $record->mostSpecificSubdivision->name ?? null,
            city: $record->city->name ?? null,
            postal: $record->postal->code ?? null,
            latitude: $record->location->latitude ?? null,
            longitude: $record->location->longitude ?? null,
        );
    }
}
