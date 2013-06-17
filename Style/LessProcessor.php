<?php

namespace WebResources\NetteManager\Style;



class LessProcessor implements IProcessor
{

	public function isSupported($filename)
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		return in_array($ext, array('less', 'css'));
	}



	public function canCompress()
	{
		return TRUE;
	}



	public function process($filename, $compress)
	{
		$command = 'lessc ' . escapeshellarg($filename);
		if ($compress) {
			$command .= ' -compress';
		}

		return shell_exec($command);
	}

}
