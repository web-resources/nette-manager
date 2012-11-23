<?php

namespace Mishak\WebResourceManagement\Style;

class SassProcessor implements IProcessor {

	public function isSupported($filename)
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		return in_array($ext, array('sass', 'scss', 'css'));
	}

	public function canCompress()
	{
		return TRUE;
	}

	public function process($filename, $compress)
	{
		$command = 'sass';
		if ($compress) {
			$command .= ' --style compressed';
		}
		$command .= ' '  . escapeshellarg($filename);
		return shell_exec($command);
	}

}
