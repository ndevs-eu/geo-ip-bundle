
<p align="center">
  <a href="https://github.com/ndevs-eu/geo-ip-bundle/actions">
    <img src="https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat-square" alt="PHPStan Level 9" />
  </a>
  <a href="https://github.com/ndevs-eu/geo-ip-bundle/actions/workflows/ci.yml">
    <img src="https://github.com/ndevs-eu/geo-ip-bundle/actions/workflows/ci.yml/badge.svg" alt="CI" />
  </a>
  <a href="https://packagist.org/packages/ndevs-eu/geo-ip-bundle">
    <img src="https://img.shields.io/packagist/v/ndevs-eu/geo-ip-bundle.svg?style=flat-square" alt="Latest Stable Version" />
  </a>
  <a href="https://packagist.org/packages/ndevs-eu/geo-ip-bundle">
    <img src="https://img.shields.io/packagist/dt/ndevs-eu/geo-ip-bundle.svg?style=flat-square" alt="Total Downloads" />
  </a>
  <a href="https://github.com/ndevs-eu/geo-ip-bundle/blob/main/LICENSE">
    <img src="https://img.shields.io/github/license/ndevs-eu/geo-ip-bundle?style=flat-square" alt="License" />
  </a>
</p>

# 🌍 NDevs GeoIP Bundle

Symfony bundle for detecting user geolocation based on IP address.  
Supports **MaxMind GeoLite2** and **IP2Location**, with optional fallback, mock IP in dev mode, and automatic request listener.

---

## ⚙️ Installation

- ``` composer require ndevs-eu/geo-ip-bundle ```

- ``` Copy example config to /config/packages/geo_ip.yaml ```

- ``` Add env variables to your .env file (GEOIP_MAXMIND_LICENSE_KEY, GEOIP_IP2LOCATION_LICENSE_KEY) ```

- ``` Run bin/console geoip:pull-maxmind-database ```

- ``` Run bin/console geoip:pull-ip2loc-lite-database ```


---

## 📁 Configuration structure (`config/packages/geo_ip.yaml`)

```yaml
geo_ip:
    listener_enabled: false # Enables request listener to attach geo data

    resolver:
        primary: maxmind       # or 'ip2location'
        fallback: ip2location  # optional

    maxmind:
        path: '%kernel.project_dir%/var/geoip-maxmind/'
        key: '%env(GEOIP_MAXMIND_LICENSE_KEY)%'

    ip2location:
        path: '%kernel.project_dir%/var/geoip-ip2loc/'
        key: '%env(GEOIP_IP2LOCATION_LICENSE_KEY)%'
```

> In `dev` environment you can add a mock IP for testing:
>
> ```yaml
> when@dev:
>     geo_ip:
>         mock_ip: '185.170.167.18'
> ```

---

## 🧪 Usage in code

### ✅ Accessing geo data

```php
$geoData = $request->attributes->get('geoIp');

if ($geoData) {
    $country = $geoData['country'];
    $city = $geoData['city'];
}
```

> The listener injects `geoIp` attribute into each request when enabled.

---

## 🌍 Downloading databases

### 📥 MaxMind (GeoLite2 City)

1. [Register at maxmind.com](https://www.maxmind.com/en/geolite2/signup)
2. Get your `License Key`
3. Download database manually or run:

```bash
php bin/console geoip:pull-maxmind-database
```

### 📥 IP2Location (Lite BIN)

1. [Get free version](https://lite.ip2location.com/)
2. Download manually or run:

```bash
php bin/console geoip:pull-ip2loc-lite-database
```

> Make sure the path in your config matches the location of extracted files.

---

## 🧪 Mocking IP in dev

To test without relying on real headers:

```yaml
when@dev:
    geo_ip:
        mock_ip: '8.8.8.8'
```

---

## 🧰 Available Console Commands

| Command                                | Description                                  |
|----------------------------------------|----------------------------------------------|
| `geoip:pull-maxmind-database`          | Downloads the latest MaxMind GeoIP DB        |
| `geoip:pull-ip2loc-lite-database`      | Downloads the latest IP2Location Lite DB     |

---

## ✅ Requirements

- PHP 8.1+
- Symfony 6.3+
- Extensions:
  - `ext-json`
  - `ext-mbstring`
  - `ext-zip`
  - `ext-curl`
- Composer
- MaxMind GeoIP2 (City or Country)

---

## 📌 Features

✅ Auto-detects IP from headers (X-Forwarded-For, Cloudflare, etc.)  
✅ Supports fallback resolver if primary fails  
✅ Allows IP mocking in dev mode  
✅ Docker/proxy/CDN-friendly  
✅ Easy integration via DI

---

## 🙋 FAQ

### What if the IP is not found?

`geoIp` attribute will contain `null` values. No exception is thrown.

### What if I don’t use IP2Location?

Just don't define a fallback resolver in config.

---

## 📄 License

MIT

## GitAds Sponsored
[![Sponsored by GitAds](https://gitads.dev/v1/ad-serve?source=ndevs-eu/geo-ip-bundle@github)](https://gitads.dev/v1/ad-track?source=ndevs-eu/geo-ip-bundle@github)

