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

	public function __construct($scripts, Nette\Localization\ITranslator $translator = NULL)
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

	private $generateGzipFile = FALSE;

	public function setGenerateGzipFile($use)
	{
		$this->generateGzipFile = $use;
		return $this;
	}

	private $outputDirectory;

	public function setOutputDirectory($dir)
	{
		if (is_dir($dir) && is_writable($dir)) {
			$this->outputDirectory = $dir;
		} else {
			throw new \Exception("Output directory must be writable directory.");
		}
	}

	private $path;

	/**
	 * Set path to styles relative to baseUri
	 *
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = rtrim($path, '/');
		return $this;
	}

	private $compressCommand;

	public function setCompressCommand($command)
	{
		$this->compressCommand = $command;
	}

	private $presenter;

	private $baseUri;

	/**
	 * Set presenter (is passed to script config)
	 *
	 * @param string $presenter
	 */
	public function setPresenter($presenter)
	{
		$this->presenter = $presenter;
		$this->baseUri = rtrim($presenter->getContext()->getService('httpRequest')->getUrl()->getBaseUrl(), '/');
		return $this;
	}

	private $tempDir;

	public function setTempDirectory($dir)
	{
		$this->tempDir = $dir;
	}

	/**
	 * Holds all translations needed by scripts
	 * @var array
	 */
	private $translations = array();

	public function output()
	{
		$this->translations = array();
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
		if ($this->translations) {
			$fragment->insert(0, $this->outputTranslations());
		}
		return $fragment;
	}

	private function outputTranslations()
	{
		$script = Html::el('script', array('type' => 'text/javascript'));
		$contents = "\nvar translations = typeof translations == 'undefined' ? {} : translations;\n";
		foreach (array_unique($this->translations) as $message) {
			$contents .= 'translations[' . json_encode($message) . '] = ' . json_encode(NULL !== $this->translator ? $this->translator->translate($message) : $message) . ";\n";
		}
		$script->setText($contents);
		return $script;
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
		$fragment = Html::el();
		if (isset($script->translations)) {
			$this->translations = array_merge($this->translations, $script->translations);
		}

		$filename = $this->generateFile($script);
		if (!empty($script->include)) {
			$fragment->create('script', array('type' => 'text/javascript'))->setText(file_get_contents($filename));
		} else {
			$fragment->create('script', array(
				'src' => parse_url($filename, PHP_URL_SCHEME) || substr($filename, 0, 2) === '//' ? $filename : $this->baseUri . '/' . $this->path . '/' . $filename,
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

	private function generateFile($script)
	{
		$usedMinified = FALSE;
		$multiple = NULL;
		$filenamePrefix = NULL;
		if ($this->usePublic && isset($script->public)) {
			return $script->public;
		} elseif (isset($script->minified) && $this->useMinified || !isset($script->filename) && !isset($script->multiple)) {
			$usedMinified = TRUE;
			$filename = $script->minified;
		} elseif (isset($script->filename)) {
			$filename = $script->filename;
		} elseif (isset($script->multiple)) {
			$multiple = $script->multiple['files'];
			$filenamePrefix = $script->multiple['dir'] . '/' . ($script->multiple['prefix'] ?: '');
		} else {
			throw new \Exception("Script '$script->name' is missing filename or its minified version.");
		}
		if (NULL !== $multiple) {
			$hashes = array();
			foreach ($multiple as $_filename) {
				$hashes[] = md5_file($filenamePrefix . $_filename);
			}
			$md5 = md5(implode(':', $hashes));
		} else {
			$md5 = md5_file($filename);
		}
		if (isset($script->copyright)) {
			$md5 = md5($md5 . ':' . md5($script->copyright));
		}
		$dir = $this->outputDirectory;
		$extension = '.js';
		if ($this->useMinified && $this->compressCommand !== NULL || $usedMinified) {
			$extension = '.min' . $extension;
		}
		$outputFilename = $md5 . $extension;
		$output = $dir . '/' . $outputFilename;
		if (!file_exists($output)) {
			$contents = NULL;
			if ($multiple) {
				$filename = $this->tempDir . '/' . $md5 . '.js';
				$f = fopen($filename, 'wb');
				foreach ($multiple as $_filename) {
					fwrite($f, file_get_contents($filenamePrefix . $_filename));
					fwrite($f, "\n");
				}
				fclose($f);
			}
			if ($usedMinified) {
				copy($filename, $output);
			} elseif ($this->useMinified && $this->compressCommand !== NULL) {
				$contents = shell_exec(sprintf($this->compressCommand, escapeshellarg($filename)));
				if (!isset($script->copyright)) {
					file_put_contents($output, $contents);
				}
			} else {
				copy($filename, $output);
			}
			if ((isset($script->copyright) || $this->generateGzipFile) && NULL === $contents) {
				$contents = file_get_contents($output);
			}
			if (isset($script->copyright)) {
				$contents = $script->copyright . "\n" . $contents;
				file_put_contents($output, $contents);
			}
			if ($this->generateGzipFile) {
				file_put_contents($output . '.gz', gzencode($contents));
				touch($output . '.gz', filemtime($output));
			}
		}
		return $outputFilename;
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
