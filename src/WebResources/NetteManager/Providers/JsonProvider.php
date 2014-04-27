<?php

namespace WebResources\NetteManager\Providers;

use Exception;
use WebResources\NetteManager\Parsers\SingleParser;



class JsonProvider extends \Nette\Object implements IProvider
{

	/** @var string */
	private $contents;

	/** @var bool */
	private $isSingle;

	/** @var object|array */
	private $data;

	/** @var \WebResources\NetteManager\Providers\ProviderContext */
	private $context;



	/**
	 * @param string
	 * @param bool
	 */
	public function __construct($contents, $isSingle, ProviderContext $context)
	{
		$this->contents = $contents;
		$this->isSingle = (bool) $isSingle;
		$this->context = $context;

		if (FALSE === ($this->data = json_decode($contents))) {
			throw new Exception("Bad JSON format");
		}

		if ($this->isSingle && FALSE === is_object($this->data)) {
			throw new Exception("Contents must be an object");

		} elseif (!$this->isSingle && FALSE === is_array($this->data)) {
			throw new Exception("Contents must be an array");

		}
	}



	public function getWebResources()
	{
		$result = array();
		$singleParser = new SingleParser($this->context);

		if ($this->isSingle) {
			$result[] = $singleParser->parseObject($this->data);

		} else {
			foreach ($this->data as $record) {
				$result[] = $singleParser->parseObject($record);
			}
		}

		return $result;
	}
}