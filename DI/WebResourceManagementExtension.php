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
		'usePublic' => FALSE,
		'useMinified' => FALSE /* parameters['productionMode'] */,
		'generateGzipFile' => FALSE,
		'scripts' => array(),
		'styles' => array()
	);



	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$this->defaults['useMinified'] = $container->parameters['productionMode'];
		$config = $this->getConfig($this->defaults);

		$scriptManager = $container->addDefinition($this->prefix('scriptManager'))
			->setClass('Mishak\WebResourceManagement\ScriptManager', array('scripts' => $config['scripts']));
		$scriptManager->addSetup('setUsePublic', array($config['usePublic']));
		$scriptManager->addSetup('setUseMinified', array($config['useMinified']));

		$styleManager = $container->addDefinition($this->prefix('styleManager'))
			->setClass('Mishak\WebResourceManagement\StyleManager', array('styles' => $config['styles']));
		$styleManager->addSetup('setUseMinified', array($config['useMinified']));
		$styleManager->addSetup('setGenerateGzipFile', array($config['generateGzipFile']));
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
