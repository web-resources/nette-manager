<?php

namespace WebResources\NetteManager\Providers;



class ProviderContext extends \Nette\Object
{

	private $baseDirectory;

	private $variables;

	public function __construct($baseDirectory, $variables = array())
	{
		$this->baseDirectory = $baseDirectory;
		$this->variables = $variables;
	}



	public function derive($baseDirectory)
	{
		return new static($baseDirectory, $this->variables);
	}
}
