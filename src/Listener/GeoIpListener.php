<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Listener;

use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use NDevsEu\GeoIp\Detector\IPDetector;
use NDevsEu\GeoIp\Service\GeoIpService;
use NDevsEu\GeoIp\ValueObject\IpAddress;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

readonly class GeoIpListener
{
    public function __construct(
        private GeoIpService $geoService,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function __invoke(RequestEvent $requestEvent): void
    {
	    $request = $requestEvent->getRequest();

	    /** @var string|null $realIp */
	    $realIp = IPDetector::getRealClientIp($request);

	    /** @var string|null $ip */
	    $ip = $this->parameterBag->get('geo_ip.mock_ip');

	    if (NULL === $ip && NULL !== $realIp) {
		    $ip = $realIp;
	    }

	    if (NULL === $ip) {
		    $request->attributes->set('geoIp', NULL);

		    return;
	    }

	    $ipAddress = new IpAddress($ip);

	    try {
		    $geoResponse = $this->geoService->lookup($ipAddress);

		    if (NULL === $geoResponse) {
			    $request->attributes->set('geoIp', NULL);

			    return;
		    }

		    $request->attributes->set('geoIp', [
			    'country' => $geoResponse->getCountry() ?? NULL,
			    'region' => $geoResponse->getRegion() ?? NULL,
			    'city' => $geoResponse->getCity() ?? NULL,
			    'postal' => $geoResponse->getPostal() ?? NULL,
			    'latitude' => $geoResponse->getLatitude() ?? NULL,
			    'longitude' => $geoResponse->getLongitude() ?? NULL,
		    ]);
	    } catch (AddressNotFoundException|InvalidDatabaseException|\Exception $e) {
		    $request->attributes->set('geoIp', NULL);
	    }
    }

}
