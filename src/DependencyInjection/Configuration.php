<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder('geo_ip');
		$rootNode = $treeBuilder->getRootNode();

		$rootNode
			->children()
			->booleanNode('listener_enabled')
			->defaultFalse()
			->end()

			->scalarNode('mock_ip')
			->defaultNull()
			->end()

			->arrayNode('resolver')
			->isRequired()
			->children()
			->scalarNode('primary')
			->isRequired()
			->validate()
			->ifNotInArray(['maxmind', 'ip2location'])
			->thenInvalid('Invalid resolver.primary: %s')
			->end()
			->end()
			->scalarNode('fallback')
			->defaultNull()
			->validate()
			->ifTrue(fn($v) => !in_array($v, ['maxmind', 'ip2location', null], true))
			->thenInvalid('Invalid resolver.fallback: %s')
			->end()
			->end()
			->end()
			->end()

			->arrayNode('maxmind')
			->addDefaultsIfNotSet()
			->children()
			->scalarNode('path')->defaultNull()->end()
			->scalarNode('key')->defaultNull()->end()
			->end()
			->end()

			->arrayNode('ip2location')
			->addDefaultsIfNotSet()
			->children()
			->scalarNode('path')->defaultNull()->end()
			->scalarNode('key')->defaultNull()->end()
			->end()
			->end()

			->end()

			->validate()
			->ifTrue(function (array $config): bool {
				$primary = $config['resolver']['primary'] ?? null;
				$fallback = $config['resolver']['fallback'] ?? null;

				$requires = function (?string $resolver) use ($config): bool {
					if ($resolver === 'maxmind') {
						return empty($config['maxmind']['path']) || empty($config['maxmind']['key']);
					}
					if ($resolver === 'ip2location') {
						return empty($config['ip2location']['path']) || empty($config['ip2location']['key']);
					}
					return false;
				};

				return $requires($primary) || $requires($fallback);
			})
			->thenInvalid('GeoIP configuration is invalid: required path and key for selected resolver(s) must be provided.')
			->end();

		return $treeBuilder;
	}
}
