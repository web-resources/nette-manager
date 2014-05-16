<?php

namespace WebResources\NetteManager\DI;

use Nette;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;


if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

if (isset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']) || !class_exists('Nette\Configurator')) {
	unset(Nette\Loaders\NetteLoader::getInstance()->renamed['Nette\Configurator']); // fuck you
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
}


class WebResourcesExtension extends CompilerExtension
{

	public $defaults = array(
		'usePublic' => FALSE,
		'useMinified' => FALSE /* parameters['productionMode'] */,
		'generateGzipFile' => FALSE,
		'scriptCompressCommand' => NULL,
		'outputDir' => '%wwwDir%/assets',
		'scripts' => array(),
		'styles' => array()
	);



	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$this->defaults['useMinified'] = $container->parameters['productionMode'];
		$config = $this->getConfig($this->defaults);

		$scriptManager = $container->addDefinition($this->prefix('scriptManager'))
			->setClass('WebResources\NetteManager\ScriptManager', array('scripts' => $config['scripts']));
		$scriptManager->addSetup('setUsePublic', array($config['usePublic']));
		$scriptManager->addSetup('setUseMinified', array($config['useMinified']));
		$scriptManager->addSetup('setGenerateGzipFile', array($config['generateGzipFile']));
		$scriptManager->addSetup('setOutputDirectory', array(rtrim($config['outputDir'])));
		$scriptManager->addSetup('setPath', array('assets'));
		$scriptManager->addSetup('setCompressCommand', array($config['scriptCompressCommand']));
		$scriptManager->addSetup('setTempDirectory', array($container->parameters['tempDir']));

		$styleManager = $container->addDefinition($this->prefix('styleManager'))
			->setClass('WebResources\NetteManager\StyleManager', array('styles' => $config['styles']));
		$styleManager->addSetup('setUseMinified', array($config['useMinified']));
		$styleManager->addSetup('setGenerateGzipFile', array($config['generateGzipFile']));
		$styleManager->addSetup('setOutputDirectory', array(rtrim($config['outputDir'])));
		$styleManager->addSetup('setPath', array('assets'));

		// register latte macros
		$engine = $container->getDefinition('nette.latteFactory');
		$install = 'WebResources\NetteManager\Latte\Macros::install';
		$engine->addSetup($install . '(?->getCompiler())', array('@self'));

		$engine = $container->getDefinition('nette.latte');
		$install = 'WebResources\NetteManager\Latte\Macros::install';
		$engine->addSetup($install . '(?->getCompiler())', array('@self'));
	}



	/**
	 * @param \Nette\Config\Configurator $config
	 */
	public static function register(Configurator $config)
	{
		$config->onCompile[] = function (Configurator $config, Compiler $compiler) {
			$compiler->addExtension('web-resources', new WebResourcesExtension);
		};
	}

}
