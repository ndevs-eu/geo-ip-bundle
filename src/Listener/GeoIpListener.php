<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Listener;

use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
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
        $realIp = $this->getRealClientIp($request);

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

    private function getRealClientIp(Request $request): ?string
    {
        // 1. Zkus X-Forwarded-For – může obsahovat víc IP, první by měla být klientská
        $forwardedFor = $request->headers->get('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if ($this->isPublicIp($ip)) {
                    return $ip;
                }
            }
        }

        // 2. Zkus další možné hlavičky
        foreach (['X-Client-IP', 'Forwarded', 'CF-Connecting-IP'] as $header) {
            $ip = $request->headers->get($header);
            if ($ip && $this->isPublicIp($ip)) {
                return $ip;
            }
        }

        // 3. Poslední možnost – getClientIp() (respektuje trusted proxies)
        $ip = $request->getClientIp();
        if ($ip && $this->isPublicIp($ip)) {
            return $ip;
        }

        return null;
    }

    private function isPublicIp(string $ip): bool
    {
        if (!filter_var($ip, \FILTER_VALIDATE_IP)) {
            return false;
        }

        return !filter_var(
            $ip,
            \FILTER_VALIDATE_IP,
            \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE
        );
    }
}
