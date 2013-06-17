Web Resources not only for Nette Framework
===========================================

Main purpose is to simplify management of scripts and styles by moving their definition to configs (of modules) and supporting automated generation of content.

**EXPERIMENTAL!** **BC breaks might occur.** This is still more of a draft then proper code so please bear with me.

## Installation

Add to your `composer.json` requirement `"web-resources/nette-manager": "dev-master"`.
Directory `%WWW_DIR%/assets` must be writable by server (can be changed in config).

### Bootstrap

	$configurator = new Nette\Config\Configurator;
	// after the line above
	WebResources\NetteManager\DI\WebResourcesExtension::register($configurator);
	// before the line bellow
	$container = $configurator->createContainer();

### In template

	<head>
		...
		{include ../../libs/web-resources/nette-manager/@styles.latte}
	</head>

	<body>
		...
		{include ../../libs/web-resources/nette-manager/@scripts.latte}
	</body>

## Capabilities

 - dependency management

### Scripts

 - translations
 - per script configuration
 - using public, minified and raw version of script based on debugMode and config

### Styles

 - support for [less](http://lesscss.org/) (you must have `lessc` in path)
 - extracting resources to new folders to prevent caching issues using new url
 - renaming file to prevent caching issues
 - generating of gzipped files - can be used by nginx using `gzip_static on;`

## Usage

In config.neon:

	resources:

		styles:
			bootstrap:
				filename: %appDir%/../libs/twitter/bootstrap/less/bootstrap.less
			bootstrap.responsive:
				filename: %appDir%/../libs/twitter/bootstrap/less/responsive.less
				depends: bootstrap

		scripts:
			jquery:
				filename: jquery-1.8.2.js
				public: //ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.js
			bootstrap:
				filename: bootstrap.js
				minified: bootstrap.min.js
				depends: jquery
			main:
				filename: main.js
				depends: [ jquery, bootstrap ]

 - attribute `depends` is optional, defaults to empty array (`[ ]`)

### Scripts

 - `minified` version is optional
 - at least `filename` or `public` must be defined
 - switching between is done automatically in this order (`productionMode = TRUE`) `public` > `minified` > `filename` (`debugMode = TRUE`)
