<?php

namespace WebResources\NetteManager\Providers;



interface IProvider
{

	/** @var \WebResources\NetteManager\WebResource[] */
	function getWebResources();

}
