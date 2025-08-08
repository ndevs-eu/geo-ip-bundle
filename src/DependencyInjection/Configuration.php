<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('geo_ip');

        $rootNode = $treeBuilder->getRootNode();

        /** @phpstan-ignore-next-line */
        $children = $rootNode->children();

        $children
            ->booleanNode('listener_enabled')->defaultFalse()->end()
            ->scalarNode('mock_ip')->defaultNull()->end();

        $resolverNode = $children->arrayNode('resolver')->isRequired();

        $resolverChildren = $resolverNode->children();

        $resolverChildren
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
            ->ifTrue(fn ($v) => !\in_array($v, ['maxmind', 'ip2location', null], true))
            ->thenInvalid('Invalid resolver.fallback: %s')
            ->end()
            ->end();

        $maxmindNode = $children->arrayNode('maxmind')->addDefaultsIfNotSet();

        $maxmindNode->children()
            ->scalarNode('path')->defaultNull()->end()
            ->scalarNode('key')->defaultNull()->end();

        $ip2locNode = $children->arrayNode('ip2location')->addDefaultsIfNotSet();

        $ip2locNode->children()
            ->scalarNode('path')->defaultNull()->end()
            ->scalarNode('key')->defaultNull()->end();

        $rootNode
            ->validate()
            ->ifTrue(function (array $config): bool {
                $primary = $config['resolver']['primary'] ?? null;
                $fallback = $config['resolver']['fallback'] ?? null;

                $requires = function (?string $resolver) use ($config): bool {
                    if ('maxmind' === $resolver) {
                        return empty($config['maxmind']['path']) || empty($config['maxmind']['key']);
                    }
                    if ('ip2location' === $resolver) {
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
