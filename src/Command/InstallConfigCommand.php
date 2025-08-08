<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'geoip:install-config',
    description: 'Installs default geo_ip.yaml config and .env variables',
)]
final class InstallConfigCommand extends Command
{
    private const CONFIG_TEMPLATE = <<<YAML
geo_ip:
    listener_enabled: false
    resolver:
        primary: maxmind
        fallback: ip2location

    maxmind:
        path: '%kernel.project_dir%/var/geoip-maxmind/'
        key: '%env(GEOIP_MAXMIND_LICENSE_KEY)%'

    ip2location:
        path: '%kernel.project_dir%/var/geoip-ip2loc/'
        key: '%env(GEOIP_IP2LOCATION_LICENSE_KEY)%'
YAML;

    private const ENV_LINES = <<<ENV
###> geo_ip ###
GEOIP_MAXMIND_LICENSE_KEY=your_maxmind_key_here
GEOIP_IP2LOCATION_LICENSE_KEY=your_ip2location_key_here
###< geo_ip ###
ENV;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $configPath = 'config/packages/geo_ip.yaml';
        if (!$fs->exists($configPath)) {
            $fs->dumpFile($configPath, self::CONFIG_TEMPLATE);
            $io->success("Configuration file created at: $configPath");
        } else {
            $io->warning("Configuration file already exists: $configPath");
        }

        $envPath = file_exists('.env.local') ? '.env.local' : '.env';

        /** @var string|false $envContent */
        $envContent = file_get_contents($envPath);

        if (false === $envContent) {
            $io->error("Failed to read the environment file: $envPath");

            return Command::FAILURE;
        }

        if (!str_contains($envContent, 'GEOIP_MAXMIND_LICENSE_KEY')) {
            file_put_contents($envPath, \PHP_EOL.self::ENV_LINES.\PHP_EOL, \FILE_APPEND);
            $io->success("Environment variables added to $envPath");
        } else {
            $io->warning("Environment variables already exist in $envPath");
        }

        $io->note('Edit your GEOIP_... keys in the .env file and download the database accordingly.');

        return Command::SUCCESS;
    }
}
