<?php

namespace WebResources\NetteManager\Parsers;

use WebResources\NetteManager\WebResource;



class SingleParser extends \Nette\Object
{

	public function parseObject($object)
	{
		return new WebResource($object->name, $object);
	}
}