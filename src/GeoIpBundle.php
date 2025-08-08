<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp;

use Symfony\Component\HttpKernel\Bundle\Bundle;


class GeoIpBundle extends Bundle
{
	public function getPath(): string
	{
		return \dirname(__DIR__); // důležité pro správné načítání relativních cest
	}
}
