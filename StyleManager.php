<?php

namespace Mishak\WebResourceManagement;

use Nette\Utils\Html;

class StyleManager {

	/**
	 * Definition of styles
	 *
	 * @var array
	 */
	private $styles = array();

	public function __construct($styles)
	{
		$this->styles = $styles;
	}

	public function setRequired($styles)
	{
		$this->required = array();
		$this->dependencies = array();
		$this->queue = array();
		foreach ($styles as $style) {
			$this->add($style);
		}
		return $this;
	}

	private $generateGzipFile = FALSE;

	public function setGenerateGzipFile($use)
	{
		$this->generateGzipFile = $use;
		return $this;
	}

	private $path;

	/**
	 * Set server path to styles
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
	 * Set presenter (is passed to style config)
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
			$styles = $this->queue;
			$this->queue = array();
			foreach ($styles as $style) {
				$fragment[] = $this->outputStyle($style);
				$fragment[] = "\n";
				$this->addStyleDependenciesToQueue($style);
			}
		}
		return $fragment;
	}

	private $minified = TRUE;

	/**
	 * All required styles
	 *
	 * @var array
	 */
	private $required;

	/**
	 * Map of style dependencies
	 *
	 * @var array[$styleName][$dependantName] = $dependant
	 */
	private $dependencies;

	/**
	 * Queue of styles to print
	 *
	 * @var array
	 */
	private $queue;

	/**
	 * Adds style identified by name to required styles
	 *
	 * @param string $name
	 * @return object
	 */
	private function add($name)
	{
		if (isset($this->required[$name])) {
			return $this->required[$name];
		}
		if (!isset($this->styles[$name])) {
			throw new \Exception("Style '$name' has no definition.");
		}
		$style = (object) $this->styles[$name];
		$style->name = $name;
		$style->printed = FALSE;
		$style->depends = isset($style->depends) ? (is_array($style->depends) ? $style->depends : array($style->depends)) : array();
		foreach ($style->depends as $dependency) {
			$this->add($dependency);
			$this->dependencies[$dependency][$style->name] = $style;
		}
		if (!$style->depends) {
			$this->queue[] = $style;
		}
		return $this->required[$name] = $style;
	}

	private function outputStyle($style)
	{
		$fragment = Html::el();
		if (!empty($style->include)) {
			$fragment->create('style', array('type' => 'text/css'))->setText(file_get_contents(WWW_DIR . '/js/' . $filename));
			$style->printed = TRUE;
			return $fragment;
		}
		$minified = $this->presenter->context->parameters['productionMode'];
		$md5 = md5_file($style->filename);
		$dir = WWW_DIR . '/generated/styles';
		$extension = '.css';
		if ($minified) {
			$extension = '.min' . $extension;
		}
		$output = $dir . '/' . $md5 . $extension;
		if (!file_exists($output)) {
			if (!is_dir($dir)) {
				mkdir($dir, 0755, TRUE);
			}
			if (!file_exists($style->filename)) {
				throw new \Exception("Missing style '$style->name' file '$style->filename'.");
			}
			$command = array( 'lessc', escapeshellarg($style->filename) );
			if ($minified) {
				$command[] = '-compress';
			}
			$contents = shell_exec(implode(' ', $command));
			$contents = preg_replace_callback('/url\(([^)]+)\)/i', function ($matches) use ($style, $output, $md5, $extension) {
				$url = trim($matches[1], '\'"');
				$dir = substr($output, 0, -strlen($extension));
				$resource = $url;
				while ('../' == substr($resource, 0, 3)) {
					$resource = substr($resource, 3);
				}
				if (!is_dir($dir . '/' . dirname($resource))) {
					mkdir($dir . '/' . dirname($resource), 0755, TRUE);
				}
				copy(dirname($style->filename) . '/' . $url, $dir . '/' . $resource);
				return 'url(' . $md5 . '/' . $resource . ')';
			}, $contents);
			file_put_contents($output, $contents);
			if ($this->generateGzipFile) {
				file_put_contents($output . '.gz', gzencode($contents));
				touch($output . '.gz', filemtime($output));
			}
		}
		$fragment->create('link', array(
			'href' => $this->path . '/generated/styles/' . $md5 . $extension,
			'rel' => 'stylesheet',
			'type' => 'text/css'
		));
		$style->printed = TRUE;
		return $fragment;
	}

	private function addStyleDependenciesToQueue($style)
	{
		if (isset($this->dependencies[$style->name])) {
			foreach ($this->dependencies[$style->name] as $dependant) {
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
