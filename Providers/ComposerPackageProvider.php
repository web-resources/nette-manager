<?php

namespace WebResources\NetteManager\Providers;

use Exception;



class ComposerPackageProvider extends \Nette\Object implements IProvider
{

	/** @var string */
	private $projectRoot;



	/**
	 * @param $projectRoot
	 */
	public function __construct($projectRoot)
	{
		$this->projectRoot = $projectRoot;
	}



	public function getWebResources()
	{
		$result = array();
		$basePackageDir = $this->projectRoot . '/' . $this->getVendorDir();
		foreach ($this->getInstalledPackages() as $package) {
			$targetDir = $basePackageDir . '/' . $package->name;
			if (isset($package->{'target-dir'})) {
				$targetDir .= '/' . $package->{'target-dir'};
			}

			$directoryProvider = new DirectoryProvider($targetDir);
			$result = array_merge($result, $directoryProvider->getWebResources());
		}

		return $result;
	}



	private function getInstalledPackages()
	{
		$composerInstalled = $this->projectRoot . '/' . $this->getVendorDir() . '/composer/installed.json';
		if (!file_exists($composerInstalled)) {
			throw new Exception("Composer has not installed any packages");
		}

		if (FALSE === ($contents = file_get_contents($composerInstalled))) {
			throw new Exception("Cannot read installed packages '$composerInstalled'");
		}

		if (FALSE === ($data = json_decode($contents))) {
			throw new Exception("Cannot parse installed packages '$composerInstalled'");
		}

		if (!is_array($data)) {
			throw new Exception("Unsupported format of installed packages '$composerInstalled'");
		}

		return $data;
	}



	private function getVendorDir()
	{
		$composerConfig = $this->projectRoot . '/composer.json';
		if (!file_exists($composerConfig)) {
			throw new Exception("Composer not found");
		}

		if (FALSE === ($contents = file_get_contents($composerConfig))) {
			throw new Exception("Cannot read composer config '$composerConfig'");
		}

		if (FALSE === ($data = json_decode($contents))) {
			throw new Exception("Cannot parse composer config '$composerConfig'");
		}

		$result = 'vendor';
		if (isset($data->config, $data->config->{'vendor-dir'})) {
			$result = $data->config->{'vendor-dir'};
		}

		return $result;
	}
}