<?php

namespace Mishak\WebResourceManagement;

use Nette,
	Nette\Utils\Html;

class ScriptManager {

	/**
	 * Definition of scripts
	 *
	 * @var array
	 */
	private $scripts = array();

	private $translator;

	public function __construct($scripts, Nette\Localization\ITranslator $translator)
	{
		$this->scripts = $scripts;
		$this->translator = $translator;
	}

	private $usePublic = FALSE;

	public function setUsePublic($use)
	{
		$this->usePublic = $use;
		return $this;
	}

	private $useMinified = FALSE;

	public function setUseMinified($use)
	{
		$this->useMinified = $use;
		return $this;
	}

	public function setRequired($scripts)
	{
		$this->required = array();
		$this->dependencies = array();
		$this->queue = array();
		foreach ($scripts as $script) {
			$this->add($script);
		}
		return $this;
	}

	private $path;

	/**
	 * Set server path to scripts
	 *
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}

	private $presenter;

	/**
	 * Set presenter (is passed to script config)
	 *
	 * @param string $presenter
	 */
	public function setPresenter($presenter)
	{
		$this->presenter = $presenter;
		return $this;
	}

	public function output()
	{
		$fragment = Html::el();
		while ($this->queue) {
			$printed = FALSE;
			$scripts = $this->queue;
			$this->queue = array();
			foreach ($scripts as $script) {
				$fragment[] = $this->outputScript($script);
				$fragment[] = "\n";
				$this->addScriptDependenciesToQueue($script);
			}
		}
		return $fragment;
	}

	private $minified = TRUE;

	/**
	 * All required scripts
	 *
	 * @var array
	 */
	private $required;

	/**
	 * Map of script dependencies
	 *
	 * @var array[$scriptName][$dependantName] = $dependant
	 */
	private $dependencies;

	/**
	 * Queue of scripts to print
	 *
	 * @var array
	 */
	private $queue;

	/**
	 * Adds script identified by name to required scripts
	 *
	 * @param string $name
	 * @return object
	 */
	private function add($name)
	{
		if (isset($this->required[$name])) {
			return $this->required[$name];
		}
		if (!isset($this->scripts[$name])) {
			throw new \Exception("Script '$name' has no definition.");
		}
		$script = (object) $this->scripts[$name];
		$script->name = $name;
		$script->printed = FALSE;
		$script->depends = isset($script->depends) ? (is_array($script->depends) ? $script->depends : array($script->depends)) : array();
		foreach ($script->depends as $dependency) {
			$this->add($dependency);
			$this->dependencies[$dependency][$script->name] = $script;
		}
		if (!$script->depends) {
			$this->queue[] = $script;
		}
		return $this->required[$name] = $script;
	}

	private function outputScript($script)
	{
		if ($this->usePublic && isset($script->public)) {
			$filename = $script->public;
		} elseif (isset($script->minified) && ($this->useMinified || !isset($script->filename))) {
			$filename = $script->minified;
		} elseif (isset($script->filename)) {
			$filename = $script->filename;
		} else {
			throw new \Exception("Script '$script->name' is missing filename or its minified version.");
		}
		$fragment = Html::el();
		if (isset($script->translations)) {
			$init = 'var translations = typeof translations == \'undefined\' ? {} : translations;';
			$content = array( $init );
			foreach ($script->translations as $text) {
				$content[] = 'translations[' . json_encode($text) . '] = ' . json_encode($this->translator ? $this->translator->translate($text) : $text) . ';';
			}
			$fragment->create('script', array('type' => 'text/javascript'))->setText("\n" . implode("\n", $content));
		}
		if (!empty($script->include)) {
			$fragment->create('script', array('type' => 'text/javascript'))->setText(file_get_contents(WWW_DIR . '/js/' . $filename));
		} else {
			$fragment->create('script', array(
				'src' => parse_url($filename, PHP_URL_SCHEME) || substr($filename, 0, 2) === '//' ? $filename : $this->path . '/' . $filename,
				'type' => 'text/javascript'
			));
		}
		if (isset($script->config)) {
			$class = $script->config['class'];
			$config = new $class;
			$variables = $config->getVariables($script->name, $this->presenter);
			$content = array();
			foreach ($variables as $name => $value) {
				$line = '';
				if (FALSE === strpos($name, '.')) {
					$line = 'var ';
				}
				$line .= $name . ' = ' . json_encode($value) . ";\n";
				$content[] = $line;
			}
			$fragment[] = "\n";
			$fragment->create('script', array('type' => 'text/javascript'))->setText("\n" . implode("\n", $content));
		}
		$script->printed = TRUE;
		return $fragment;
	}

	private function addScriptDependenciesToQueue($script)
	{
		if (isset($this->dependencies[$script->name])) {
			foreach ($this->dependencies[$script->name] as $dependant) {
				if ($dependant->printed) {
					continue;
				}
				$pendingDependencies = FALSE;
				foreach ($dependant->depends as $name) {
					if (!$this->required[$name]->printed) {
						$pendingDependencies = TRUE;
						break;
					}
				}
				if (!$pendingDependencies) {
					$this->queue[] = $dependant;
				}
			}
		}
	}

}
