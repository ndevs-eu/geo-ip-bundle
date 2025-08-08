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
readonly class PullIp2LocationLiteDatabaseCommand
{

	public function __construct(
		private ParameterBagInterface $parameterBag
	)
	{
	}

	public function __invoke(OutputInterface $output, InputInterface $input): int
	{
		$output->writeln('<info>Pulling the latest IP2Location Lite database...</info>');


		$ip2locationPath = $this->parameterBag->get('geo_ip.ip2location.path');
		$ip2locationKey = $this->parameterBag->get('geo_ip.ip2location.key');

		$output->writeln(sprintf('<comment>Using database path: %s</comment>', $ip2locationPath));

		self::install(
			licenseKey: $ip2locationKey,
			targetDir: $ip2locationPath
		);

		$output->writeln('<info>IP2Location Lite database has been successfully updated.</info>');

		return Command::SUCCESS;
	}

	public static function install(string $licenseKey, string $targetDir = 'var/geoip'): void
	{
		$url = "https://download.ip2location.com/lite/IP2LOCATION-LITE-DB1.BIN.ZIP?license_key={$licenseKey}&suffix=ZIP";

		$zipPath = $targetDir . '/ip2location.zip';

		if (!is_dir($targetDir)) {
			mkdir($targetDir, 0777, TRUE);
		}

		// Download ZIP
		$ch = curl_init($url);
		$fp = fopen($zipPath, 'w+');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);

		// Extract ZIP
		$zip = new \ZipArchive();
		if ($zip->open($zipPath) !== TRUE) {
			throw new \RuntimeException('Failed to open IP2Location ZIP archive.');
		}

		$zip->extractTo($targetDir);
		$zip->close();
		unlink($zipPath);

		// Find .BIN file
		$binFile = NULL;
		foreach (scandir($targetDir) as $file) {
			if (str_ends_with($file, '.BIN')) {
				$binFile = $targetDir . '/' . $file;
				break;
			}
		}

		if (!$binFile || !is_file($binFile)) {
			throw new \RuntimeException('BIN file not found in extracted archive.');
		}

		rename($binFile, $targetDir . '/DB.BIN');

		// Cleanup other files (e.g., readme.txt, license.txt)
		foreach (scandir($targetDir) as $item) {
			if (!in_array($item, ['.', '..', 'DB.BIN'], TRUE)) {
				$path = $targetDir . '/' . $item;
				is_file($path) ? unlink($path) : self::recursiveRemoveFolder($path);
			}
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
