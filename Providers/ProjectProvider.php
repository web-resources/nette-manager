<?php

namespace WebResources\NetteManager\Providers;



class ProjectProvider extends \Nette\Object implements IProvider
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
		$directoryProvider = new DirectoryProvider($this->projectRoot);

		return $directoryProvider->getResources();
	}
}