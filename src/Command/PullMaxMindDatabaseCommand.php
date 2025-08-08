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
readonly class PullMaxMindDatabaseCommand
{

	public function __construct(
		private ParameterBagInterface $parameterBag
	)
	{
	}

	public function __invoke(OutputInterface $output, InputInterface $input): int
	{
		$output->writeln('<info>Pulling the latest MaxMind GeoIP database...</info>');


		$maxMindDbPath = $this->parameterBag->get('geo_ip.maxmind.path');
		$maxMindDbKey = $this->parameterBag->get('geo_ip.maxmind.key');

		$output->writeln(sprintf('<comment>Using database path: %s</comment>', $maxMindDbPath));

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

		$url = sprintf('%s?edition_id=%s&license_key=%s&suffix=tar.gz', $baseUrl, $edition, $licenseKey);

		$archivePath = $targetDir . '/geoip.tar.gz';

		if (!is_dir($targetDir)) {
			mkdir($targetDir, 0777, true);
		}

		// Download .tar.gz
		$ch = curl_init($url);
		$fp = fopen($archivePath, 'w+');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);

		// Decompress .tar.gz → .tar
		$phar = new \PharData($archivePath);
		$phar->decompress(); // creates geoip.tar
		$tarPath = str_replace('.gz', '', $archivePath);

		// Extract .tar
		$tar = new \PharData($tarPath);
		$tar->extractTo($targetDir, null, true);

		// Find .mmdb
		$mmdbFile = null;
		$rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($targetDir));
		foreach ($rii as $file) {
			if ($file->isFile() && str_ends_with($file->getFilename(), '.mmdb')) {
				$mmdbFile = $file->getRealPath();
				break;
			}
		}

		if (!$mmdbFile) {
			throw new \RuntimeException('GeoIP MMDB file not found in archive.');
		}

		// Move and rename to GeoIp.mmdb
		$targetPath = $targetDir . '/GeoIp.mmdb';
		rename($mmdbFile, $targetPath);

		// Cleanup
		unlink($archivePath);
		unlink($tarPath);

		foreach (scandir($targetDir) as $item) {
			if (in_array($item, ['.', '..', 'GeoIp.mmdb'], true)) {
				continue;
			}

			$path = $targetDir . '/' . $item;
			is_dir($path) ? self::recursiveRemoveFolder($path) : unlink($path);
		}
	}

	private static function recursiveRemoveFolder(string $dir): void
	{
		foreach (scandir($dir) as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$path = "$dir/$item";
			is_dir($path) ? self::recursiveRemoveFolder($path) : unlink($path);
		}
		rmdir($dir);
	}

}
