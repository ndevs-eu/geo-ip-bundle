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
	)
	{
		$ip2LocationPath = $parameterBag->get('geo_ip.ip2location.path');

		if (!is_file($ip2LocationPath . 'DB.BIN')) {
			throw new \RuntimeException("IP2Location .BIN file not found: $ip2LocationPath");
		}

		$this->database = new Database($ip2LocationPath . 'DB.BIN', Database::FILE_IO);
	}

	public function lookup(IpAddress $address): ?GeoResponse
	{
		try {
			$record = $this->database->lookup($address->getAddress(), Database::ALL);

			return new GeoResponse(
				country: $record['countryCode'] ?? null,
				region: null, // free DB doesn't contain region
				city: null,   // free DB doesn't contain city
				postal: null,
				latitude: isset($record['latitude']) ? (float)$record['latitude'] : null,
				longitude: isset($record['longitude']) ? (float)$record['longitude'] : null
			);
		} catch (\Throwable $e) {
			// nepodařilo se najít, vrať null
			return null;
		}
	}

}
