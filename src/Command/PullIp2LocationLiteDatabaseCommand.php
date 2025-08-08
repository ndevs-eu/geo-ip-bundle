<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'geoip:pull-ip2loc-lite-database',
    description: 'Pulls the latest ip2loc lite database.'
)]
 class PullIp2LocationLiteDatabaseCommand extends Command
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {
		parent::__construct();
    }

    public function __invoke(OutputInterface $output, InputInterface $input): int
    {
        $output->writeln('<info>Pulling the latest IP2Location Lite database...</info>');

        /** @var string|null $ip2locationPath */
        $ip2locationPath = $this->parameterBag->get('geo_ip.ip2location.path');

        /** @var string|null $ip2locationKey */
        $ip2locationKey = $this->parameterBag->get('geo_ip.ip2location.key');

        if (null === $ip2locationPath && null === $ip2locationKey) {
            $output->writeln('<error>IP2Location path or key is not configured. Please set "geo_ip.ip2location.path" and "geo_ip.ip2location.key" in your parameters.</error>');

            return Command::FAILURE;
        } else {
            $output->writeln(\sprintf('<comment>Using database path: %s</comment>', $ip2locationPath));

            self::install(
                licenseKey: $ip2locationKey,
                targetDir: $ip2locationPath
            );

            $output->writeln('<info>IP2Location Lite database has been successfully updated.</info>');

            return Command::SUCCESS;
        }
    }

    public static function install(?string $licenseKey, ?string $targetDir = 'var/geoip'): void
    {
        if (null === $licenseKey || null === $targetDir) {
            throw new \InvalidArgumentException('License key and target directory must be provided.');
        }

        $url = "https://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.BIN.ZIP?license_key={$licenseKey}&suffix=ZIP";

        $zipPath = $targetDir.'/ip2location.zip';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Download ZIP
        /** @var \CurlHandle|false $ch */
        $ch = curl_init($url);

        if (!$ch instanceof \CurlHandle) {
            throw new \RuntimeException('Unable to initialize cURL');
        }

        /** @var resource|false $fp */
        $fp = fopen($zipPath, 'w+');

        if (!$fp) {
            throw new \RuntimeException('Unable to open file for writing: '.$zipPath);
        }

        curl_setopt($ch, \CURLOPT_FILE, $fp);
        curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, \CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        // Extract ZIP
        $zip = new \ZipArchive();
        if (true !== $zip->open($zipPath)) {
            throw new \RuntimeException('Failed to open IP2Location ZIP archive.');
        }

        $zip->extractTo($targetDir);
        $zip->close();
        unlink($zipPath);

        // Find .BIN file
        $binFile = null;

        /** @var list<string>|false $zipDir */
        $zipDir = scandir($targetDir);

        if (false === $zipDir) {
            throw new \RuntimeException('Failed to read target directory: '.$targetDir);
        }

        foreach ($zipDir as $file) {
            if (str_ends_with($file, '.BIN')) {
                $binFile = $targetDir.'/'.$file;
                break;
            }
        }

        if (!$binFile || !is_file($binFile)) {
            throw new \RuntimeException('BIN file not found in extracted archive.');
        }

        rename($binFile, $targetDir.'/DB.BIN');

        /** @var list<string>|false $dir */
        $dir = scandir($targetDir);

        if (false === $dir) {
            throw new \RuntimeException('Failed to read target directory: '.$targetDir);
        }

        foreach ($dir as $item) {
            if (!\in_array($item, ['.', '..', 'DB.BIN'], true)) {
                $path = $targetDir.'/'.$item;
                is_file($path) ? unlink($path) : self::recursiveRemoveFolder($path);
            }
        }
    }

    private static function recursiveRemoveFolder(string $targetDir): void
    {
        /** @var list<string>|false $dir */
        $dir = scandir($targetDir);

        if (false === $dir) {
            throw new \RuntimeException('Failed to read target directory: '.$targetDir);
        }
        foreach ($dir as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = "$targetDir/$item";
            is_dir($path) ? self::recursiveRemoveFolder($path) : unlink($path);
        }
        rmdir($targetDir);
    }
}
