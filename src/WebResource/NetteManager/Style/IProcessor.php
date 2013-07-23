<?php

namespace WebResources\NetteManager\Style;



interface IProcessor
{

	/**
	 * @param string $filename
	 * @return bool
	 */
	function isSupported($filename);

	/**
	 * @return bool
	 */
	function canCompress();

	/**
	 * Process file and return output
	 *
	 * @param string $filename
	 * @param bool $compress
	 * @return string
	 */
	function process($filename, $compress);

}
