<?php

namespace WebResources\NetteManager;

use Nette\Utils\Html;



class StyleManager implements IStyleManager
{

	/**
	 * Definition of styles
	 *
	 * @var array
	 */
	private $styles = array();



	public function __construct($styles)
	{
		$this->styles = $styles;
		$this->processors = array(
			new Style\StaticProcessor,
			new Style\LessProcessor,
			new Style\SassProcessor,
		);
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



	private $useMinified = FALSE;

	public function setUseMinified($use)
	{
		$this->useMinified = $use;
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



	private $presenter;

	private $baseUri;

	/**
	 * Set presenter (is passed to style config)
	 *
	 * @param string $presenter
	 */
	public function setPresenter($presenter)
	{
		$this->presenter = $presenter;
		$this->baseUri = rtrim($presenter->getContext()->getService('httpRequest')->getUrl()->getBaseUrl(), '/');
		foreach ($this->styles as $name => $style)
		{
			if (isset($style['component']) && iterator_count($presenter->getComponents(TRUE, $style['component']))) {
				$this->add($name);
			}
		}
		return $this;
	}



	public function output()
	{
		$fragment = Html::el();
		while ($this->queue) {
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



	/**
	 * All required styles
	 *
	 * @var array
	 */
	private $required = array();

	/**
	 * Map of style dependencies
	 *
	 * @var array[$styleName][$dependantName] = $dependant
	 */
	private $dependencies = array();

	/**
	 * Queue of styles to print
	 *
	 * @var array
	 */
	private $queue = array();

	/**
	 * Adds style identified by name to required styles
	 *
	 * @param string $name
	 * @return self
	 */
	public function add($name)
	{
		if (is_array($name)) {
			foreach ($name as $_name) {
				$this->add($_name);
			}
			return $this;
		}

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
		if (!isset($style->placeholder)) {
			if (!file_exists($style->filename)) {
				throw new \Exception("Missing style '$style->name' file '$style->filename'.");
			}

			if (!empty($style->include)) {
				$fragment->create('style', array('type' => 'text/css'))->setText(file_get_contents($style->filename));
				$style->printed = TRUE;
				return $fragment;
			}
			$filename = $this->generateFile($style);
			$fragment->create('link', array(
				'href' => $this->baseUri . '/' . $this->path . '/' . $filename,
				'rel' => 'stylesheet',
				'type' => 'text/css'
			));
		}

		$style->printed = TRUE;
		if (!empty($this->presenter->getContext()->parameters['debugMode'])) {
			$fragment = Html::el()->add($fragment)->add('<!-- ' . $style->name . ' -->');
		}

		return $fragment;
	}



	private function generateFile($style)
	{
		$source = $style->filename;
		$md5 = md5_file($source);
		$dir = $this->outputDirectory;
		$extension = '.css';
		if ($this->useMinified) {
			$extension = '.min' . $extension;
		}
		$filename = $md5 . $extension;
		$output = $dir . '/' . $filename;
		if (!file_exists($output)) {
			$processor = $this->getProcessor($source);
			$contents = $processor->process($source, $this->useMinified);
			$contents = $this->copyContentsResources($contents, $source, $dir . '/' . $md5);
			file_put_contents($output, $contents);
			if ($this->generateGzipFile) {
				file_put_contents($output . '.gz', gzencode($contents));
				touch($output . '.gz', filemtime($output));
			}
		}

		return $filename;
	}



	private function getProcessor($filename)
	{
		foreach ($this->processors as $processor) {
			if ($this->useMinified && !$processor->canCompress()) {
				continue;
			}
			if ($processor->isSupported($filename)) {
				return $processor;
			}
		}
		throw new \Exception("There is no available processor for '$filename' (compression: " . ($this->useMinified ? 'required' : '') . ').');
	}

	private function copyContentsResources($contents, $source, $targetDir)
	{
		$target = basename($targetDir);
		$sourceDir = dirname($source);

		return preg_replace_callback('/url\(([^)]+)\)/i', function ($matches) use ($sourceDir, $target, $targetDir) {
			preg_match('/^([^?#]*)(.*)$/', trim($matches[1], '\'"'), $matches);
			$url = $matches[1];
			$suffix = isset($matches[2]) ? $matches[2] : '';
			$resource = preg_replace('/^(\.\.\/)+/', '', $url);
			$resourceDir = $targetDir . '/' . dirname($resource);
			if (!is_dir($resourceDir)) {
				mkdir($resourceDir, 0755, TRUE);
			}
			copy($sourceDir . '/' . $url, $targetDir . '/' . $resource);

			return 'url(' . $target . '/' . $resource . $suffix . ')';
		}, $contents);
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
