<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Detector;

use Symfony\Component\HttpFoundation\Request;

class IPDetector
{
    public static function getRealClientIp(Request $request): ?string
    {
        $forwardedFor = $request->headers->get('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = explode(',', $forwardedFor);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (self::isPublicIp($ip)) {
                    return $ip;
                }
            }
        }

        foreach (['X-Client-IP', 'Forwarded', 'CF-Connecting-IP'] as $header) {
            $ip = $request->headers->get($header);
            if ($ip && self::isPublicIp($ip)) {
                return $ip;
            }
        }

        $ip = $request->getClientIp();
        if ($ip && self::isPublicIp($ip)) {
            return $ip;
        }

        return null;
    }

    public static function isPublicIp(string $ip): bool
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
