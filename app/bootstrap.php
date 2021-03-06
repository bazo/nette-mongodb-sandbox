<?php

/**
 * My Application bootstrap file.
 */
use Nette\Application\Routers\Route;


// Load Nette Framework
define('VENDORS_DIR', __DIR__ . '/../vendor');
require_once VENDORS_DIR . '/nette/nette/Nette/loader.php';
require_once VENDORS_DIR . '/autoload.php';

// Configure application
$configurator = new Nette\Config\Configurator;

// Enable Nette Debugger for error visualisation & logging
//$configurator->setProductionMode($configurator::AUTO);
$configurator->enableDebugger(__DIR__ . '/../log');

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(APP_DIR)
	->addDirectory(LIBS_DIR)
	->register();

$configurator->addParameters(array('libsDir' => LIBS_DIR));

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__ . '/config/config.neon');

$configurator->onCompile[] = function($configurator, $compiler) {
		$compiler->addExtension('documentManagerExtension', new \Extensions\DocumentManagerExtension);
		$compiler->addExtension('appCommands', new \Extensions\AppCommandsExtension);
        $compiler->addExtension('doctrineODMCommands', new \Extensions\DoctrineODMCommandsExtension);
		$compiler->addExtension('consoleApp', new \Extensions\ConsoleExtension);
};

$container = $configurator->createContainer();

if(PHP_SAPI === 'cli')
{
	$container->console->run();
}
else
{
	// Setup router
	$container->router[] = new Route('index.php', 'Homepage:default', Route::ONE_WAY);
	$container->router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');


	// Configure and run the application!
	$container->application->run();
}

