<?php
declare(strict_types=1);

namespace NDevsEu\GeoIp\DependencyInjection;

use NDevsEu\GeoIp\Listener\GeoIpListener;
use NDevsEu\GeoIp\Locator\FallbackLocator;
use NDevsEu\GeoIp\Locator\Ip2LocationLocator;
use NDevsEu\GeoIp\Locator\LocatorInterface;
use NDevsEu\GeoIp\Locator\MaxMindLocator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;

class GeoIpExtension extends Extension
{
	public function load(array $configs, ContainerBuilder $container): void
	{
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

		$loader->load('services.yaml');

		$config = $this->processConfiguration(new Configuration(), $configs);

		$container->setParameter('geo_ip.mock_ip', $config['mock_ip'] ?? null);

		$container->setParameter('geo_ip.maxmind.path', $config['maxmind']['path']);

		$container->setParameter('geo_ip.maxmind.key', $config['maxmind']['key']);

		if (!empty($config['ip2location']['path'])) {
			$container->setParameter('geo_ip.ip2location.path', $config['ip2location']['path']);
		}
		if (!empty($config['ip2location']['key'])) {
			$container->setParameter('geo_ip.ip2location.key', $config['ip2location']['key']);
		}


		$resolvers = [
			'maxmind' => new Reference(MaxMindLocator::class),
			'ip2location' => new Reference(Ip2LocationLocator::class),
		];

		$aliases = [
			'maxmind' => MaxMindLocator::class,
			'ip2location' => Ip2LocationLocator::class,
		];

		$primaryId = $config['resolver']['primary'];
		$fallbackId = $config['resolver']['fallback'] ?? null;

		$primary = $resolvers[$primaryId] ?? throw new \InvalidArgumentException("Unknown resolver: $primaryId");

		if ($fallbackId && isset($resolvers[$fallbackId])) {
			$fallback = $resolvers[$fallbackId];

			$container->register(FallbackLocator::class, FallbackLocator::class)
				->setArguments([$primary, $fallback, new Reference(LoggerInterface::class)])
				->setPublic(true);

			$container->setAlias(LocatorInterface::class, FallbackLocator::class);
		} else {
			$container->setAlias(LocatorInterface::class, $aliases[$primaryId]);
		}

		if ($config['listener_enabled']) {
			$container->register(GeoIpListener::class)
				->addTag('kernel.event_listener', [
					'event' => 'kernel.request',
					'priority' => 90,
				])
				->setAutowired(true)
				->setAutoconfigured(true)
				->setPublic(false);
		}
	}
}
