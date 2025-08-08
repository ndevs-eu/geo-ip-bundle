<?php

declare(strict_types=1);

namespace NDevsEu\GeoIp\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'geoip:pull-maxmind-database',
    description: 'Pulls the latest MaxMind GeoIP database.'
)]
class PullMaxMindDatabaseCommand extends Command
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

	public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Pulling the latest MaxMind GeoIP database...</info>');

        $maxMindDbPath = $this->parameterBag->get('geo_ip.maxmind.path');
        $maxMindDbKey = $this->parameterBag->get('geo_ip.maxmind.key');

        if (!\is_string($maxMindDbPath) || !\is_string($maxMindDbKey)) {
            throw new \RuntimeException('GeoIP MaxMind parameters must be defined as strings.');
        }

        $output->writeln(\sprintf('<comment>Using database path: %s</comment>', $maxMindDbPath));

        self::install(
            licenseKey: $maxMindDbKey,
            targetDir: $maxMindDbPath
        );

        $output->writeln('<info>MaxMind GeoIP database has been successfully updated.</info>');

        return Command::SUCCESS;
    }

    public static function install(
        string $licenseKey,
        string $targetDir,
        string $edition = 'GeoLite2-City',
    ): void {
        $baseUrl = 'https://download.maxmind.com/app/geoip_download';
        $url = \sprintf('%s?edition_id=%s&license_key=%s&suffix=tar.gz', $baseUrl, $edition, $licenseKey);
        $archivePath = $targetDir.'/geoip.tar.gz';

        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create target directory: '.$targetDir);
        }

        $ch = curl_init($url);
        if (!$ch instanceof \CurlHandle) {
            throw new \RuntimeException('Unable to initialize cURL');
        }

        $fp = fopen($archivePath, 'w+');
        if (false === $fp) {
            throw new \RuntimeException('Unable to open file for writing: '.$archivePath);
        }

        curl_setopt($ch, \CURLOPT_FILE, $fp);
        curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, \CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        try {
            $phar = new \PharData($archivePath);
            $phar->decompress(); // creates geoip.tar
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to decompress archive: '.$e->getMessage(), 0, $e);
        }

        $tarPath = str_replace('.gz', '', $archivePath);

        try {
            $tar = new \PharData($tarPath);
            $tar->extractTo($targetDir, null, true);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to extract archive: '.$e->getMessage(), 0, $e);
        }

        // Find .mmdb file
        $mmdbFile = null;
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($targetDir));
        foreach ($rii as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isFile() && str_ends_with($file->getFilename(), '.mmdb')) {
                $realPath = $file->getRealPath();
                if (false !== $realPath) {
                    $mmdbFile = $realPath;
                    break;
                }
            }
        }

        if (null === $mmdbFile) {
            throw new \RuntimeException('GeoIP MMDB file not found in archive.');
        }

        $targetPath = $targetDir.'/GeoIp.mmdb';
        if (!rename($mmdbFile, $targetPath)) {
            throw new \RuntimeException('Failed to move .mmdb file to final location.');
        }

        // Cleanup
        if (!unlink($archivePath)) {
            throw new \RuntimeException('Failed to remove archive: '.$archivePath);
        }
        if (!unlink($tarPath)) {
            throw new \RuntimeException('Failed to remove tar: '.$tarPath);
        }

        /** @var list<string>|false $dir */
        $dir = scandir($targetDir);

        if (false === $dir) {
            throw new \RuntimeException('Failed to read target directory: '.$targetDir);
        }

        foreach ($dir as $item) {
            if (\in_array($item, ['.', '..', 'GeoIp.mmdb'], true)) {
                continue;
            }
            $path = $targetDir.'/'.$item;
            if (is_dir($path)) {
                self::recursiveRemoveFolder($path);
            } else {
                unlink($path);
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
