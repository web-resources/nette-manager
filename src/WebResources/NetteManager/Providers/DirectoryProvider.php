<?php

namespace WebResources\NetteManager\Providers;

use Exception;



class DirectoryProvider extends \Nette\Object implements IProvider
{

	/** @var string */
	private $directory;

	/** @var ProviderContext */
	private $context;



	/**
	 * @param string
	 * @param ProviderContext|NULL
	 */
	public function __construct($directory, $context = NULL)
	{
		$this->directory = $directory;
		if (NULL === $context) {
			$this->context = new ProviderContext($this->directory);

		} else {
			$this->context = $context->derive($this->directory);
		}
	}



	public function getWebResources()
	{
		$singleFilename = 'web-resource.json';
		$multipleFilename = 'web-resources.json';
		$isSinglePresent = file_exists($singleFilename);
		$isMultiplePresent = file_exists($multipleFilename);

		if ($isSinglePresent && $isMultiplePresent) {
			throw new Exception("Directory '$this->directory' contains both $singleFilename and $multipleFilename");
		}

		if ($isSinglePresent || $isMultiplePresent) {
			$jsonProvider = new JsonProvider($isSinglePresent ? $singleFilename : $multipleFilename, $isSinglePresent, $this->context);

			return $jsonProvider->getWebResources();

		} else {

			return array();
		}
	}
}