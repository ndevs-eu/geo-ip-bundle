<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\ValueObject;

readonly class GeoResponse
{
    public function __construct(
        private ?string $countryName,
        private ?string $countryIsoCode,
        private ?string $region,
        private ?string $city,
        private ?string $postal,
        private ?float $latitude,
        private ?float $longitude,
    ) {
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function getCountryIsoCode(): ?string
    {
        return $this->countryIsoCode;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getPostal(): ?string
    {
        return $this->postal;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }
}
