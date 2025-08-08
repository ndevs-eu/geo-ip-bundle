<?php
declare(strict_types=1);

namespace NDevsEu\GeoIp\Locator;

use NDevsEu\GeoIp\ValueObject\GeoResponse;
use NDevsEu\GeoIp\ValueObject\IpAddress;
use Psr\Log\LoggerInterface;

readonly class FallbackLocator implements LocatorInterface
{
	public function __construct(
		private LocatorInterface $primary,
		private LocatorInterface $fallback,
		private LoggerInterface  $logger,
	) {}

	public function lookup(IpAddress $address): ?GeoResponse
	{
		try {
			$result = $this->primary->lookup($address);
			if ($result !== null) {
				return $result;
			}
		} catch (\Throwable $e) {
			$this->logger->warning('Primary GeoIP resolver failed', [
				'ip' => $address->getAddress(),
				'error' => $e->getMessage(),
			]);
		}

		return $this->fallback->lookup($address);
	}
}
