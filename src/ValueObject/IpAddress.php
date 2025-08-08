<?php
declare(strict_types=1);

namespace NDevsEu\GeoIp\ValueObject;

final readonly class IpAddress
{
	private string $address;

	private int $version;

	public function __construct(string $ip)
	{
		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			throw new \InvalidArgumentException("Invalid IP address: '$ip'");
		}

		$this->address = $ip;
		$this->version = $this->detectVersion($ip);
	}

	private function detectVersion(string $ip): int
	{
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return 4;
		}

		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return 6;
		}

		throw new \InvalidArgumentException("Unknown IP version: '$ip'");
	}

	public function __toString(): string
	{
		return $this->address;
	}

	public function isPrivate(): bool
	{
		return !filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
	}

	public function isLoopback(): bool
	{
		return !filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE);
	}

	public function isPublic(): bool
	{
		return filter_var(
				$this->address,
				FILTER_VALIDATE_IP,
				FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			) !== false;
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	public function getVersion(): int
	{
		return $this->version;
	}

}
