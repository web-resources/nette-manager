<?php

namespace WebResources\NetteManager\Providers;

use Exception;



class DirectoryProvider extends \Nette\Object implements IProvider
{

	/** @var string */
	private $directory;



	/**
	 * @param $directory
	 */
	public function __construct($directory)
	{
		$this->directory = $directory;
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
			$jsonProvider = new JsonProvider($isSinglePresent ? $singleFilename : $multipleFilename, $isSinglePresent);

			return $jsonProvider->getWebResources();

		} else {

			return array();
		}
	}
}