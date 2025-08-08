<?php

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__ . '/src')
	->in(__DIR__ . '/tests')
	->name('*.php')
	->notName('*.blade.php')
	->ignoreDotFiles(true)
	->ignoreVCS(true);

$config = new PhpCsFixer\Config();
return $config
	->setRiskyAllowed(true)
	->setRules([
		'@Symfony' => true,
		'@Symfony:risky' => true,
		'strict_comparison' => true,
		'declare_strict_types' => true,
		'no_unused_imports' => true,
		'php_unit_method_casing' => ['case' => 'camel_case'],
		'phpdoc_order' => true,
	])
	->setFinder($finder);
