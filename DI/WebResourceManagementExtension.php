<?php

namespace Mishak\WebResourceManagement\DI;

use Nette;
use Nette\Config\Configurator;
use Nette\Config\Compiler;
use Nette\DI\ContainerBuilder;
use Nette\DI\Statement;
use Nette\Utils\Validators;

class WebResourceManagementExtension extends Nette\Config\CompilerExtension
{

	/**
	 * @var array
	 */
	public $defaults = array(
		'scripts' => array(),
		'styles' => array()
	);



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('scriptManager'))
			->setClass('Mishak\WebResourceManagement\ScriptManager', array(
				'scripts' => $config['scripts']
			));

		$builder->addDefinition($this->prefix('styleManager'))
			->setClass('Mishak\WebResourceManagement\StyleManager', array(
				'styles' => $config['styles']
			));
	}



	/**
	 * @param \Nette\Config\Configurator $config
	 */
	public static function register(Configurator $config)
	{
		$config->onCompile[] = function (Configurator $config, Compiler $compiler) {
			$compiler->addExtension('resources', new WebResourceManagementExtension);
		};
	}

}
