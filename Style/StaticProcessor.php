<?php

namespace Mishak\WebResourceManagement\Style;

class StaticProcessor implements IProcessor {

	public function isSupported($filename)
	{
		return 'css' === strtolower(pathinfo($filename, PATHINFO_EXTENSION));
	}

	public function canCompress()
	{
		return FALSE;
	}

	public function process($filename, $compress)
	{
		return file_get_contents($filename);
	}

}
