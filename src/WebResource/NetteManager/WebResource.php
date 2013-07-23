<?php

namespace WebResources\NetteManager;



class WebResource extends \Nette\Object
{

	private $name;

	private $configuration;

	private $basePath;

	public $printed = FALSE;



	public function __construct($name, $configuration, $basePath)
	{
		$this->name = $name;
		$this->configuration = $configuration;
		$this->basePath = $basePath;
	}



	function __get($name)
	{
		if (isset($this->configuration->$name)) {
			return $this->configuration->$name;

		} else {
			return parent::__get($name);
		}
	}
}
