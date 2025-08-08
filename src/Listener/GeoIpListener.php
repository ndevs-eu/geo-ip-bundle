<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Listener;

use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use NDevsEu\GeoIp\Detector\IPDetector;
use NDevsEu\GeoIp\Service\GeoIpService;
use NDevsEu\GeoIp\ValueObject\IpAddress;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

        if (null === $ip && null !== $realIp) {
            $ip = $realIp;
        }

        if (null === $ip) {
            $request->attributes->set('geoIp', null);

            return;
        }

        $ipAddress = new IpAddress($ip);

        try {
            $geoResponse = $this->geoService->lookup($ipAddress);

            if (null === $geoResponse) {
                $request->attributes->set('geoIp', null);

                return;
            }

            $request->attributes->set('geoIp', [
                'country' => $geoResponse->getCountry() ?? null,
                'region' => $geoResponse->getRegion() ?? null,
                'city' => $geoResponse->getCity() ?? null,
                'postal' => $geoResponse->getPostal() ?? null,
                'latitude' => $geoResponse->getLatitude() ?? null,
                'longitude' => $geoResponse->getLongitude() ?? null,
            ]);
        } catch (AddressNotFoundException|InvalidDatabaseException|\Exception $e) {
            $request->attributes->set('geoIp', null);
        }
    }
}
