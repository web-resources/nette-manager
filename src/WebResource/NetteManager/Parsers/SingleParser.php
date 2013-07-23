<?php

namespace WebResources\NetteManager\Parsers;

use WebResources\NetteManager\Providers\ProviderContext;



class SingleParser extends \Nette\Object
{

	/** @var ProviderContext */
	private $context;



	/**
	 * @param ProviderContext $context
	 */
	public function __construct($context = NULL)
	{
		$this->context = $context;
	}



	public function parseObject($object)
	{
		$object = clone $object;
		$basePath = $this->context->getBasePath();
		foreach (array('filename', 'minified', 'multiple') as $key) {
			if (isset($object->$key)) {
				$object->$key = $this->expandPath($object->$key, $basePath);
			}
		}

		return new WebResource($object->name, $object);
	}



	/**
	 * @param array|string
	 * @param string
	 * @return array|string
	 * @internal
	 */
	public function expandPath($value, $basePath)
	{
		if (is_array($value)) {
			$self = $this;
			array_walk($value, function (&$value) use ($basePath, $self) {
				$value = $self->expandPath($value, $basePath);
			});

		} elseif (1 === preg_match('/^\.[\\/]/', $value)) {
			$value = $basePath . '/' . substr($value, 2);

		} elseif (0 === preg_match('/^[\\/]/', $value)) {
			$value = $basePath . '/' . $value;
		}

		return $value;
	}
}