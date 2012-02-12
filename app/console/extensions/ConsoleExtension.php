<?php
namespace Extensions;

use Nette\Config\Configurator,
	Nette\Framework;

/**
 * Console service.
 *
 * @author	Martin Bažík
 */
class ConsoleExtension extends \Nette\Config\CompilerExtension
{
	/**
	 * Processes configuration data
	 *
	 * @return void
	 */
	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		// console application
		$container->addDefinition($this->prefix('console'))
			->setClass('Symfony\Component\Console\Application')
			->setFactory('Extensions\ConsoleExtension::createConsole', array('@container'))
			->setAutowired(FALSE);

		// aliases
		$container->addDefinition('console')
			->setClass('Symfony\Component\Console\Application')
			->setFactory('@container::getService', array($this->prefix('console')));
	}

	/**
	 * @param \Nette\DI\Container
	 * @param \Symfony\Component\Console\Helper\HelperSet
	 * @return \Symfony\Component\Console\Application
	 */
	public static function createConsole(\Nette\DI\Container $container,
		\Symfony\Component\Console\Helper\HelperSet $helperSet = NULL)
	{
		$console = new \Symfony\Component\Console\Application(
			Framework::NAME . " Command Line Interface", Framework::VERSION
		);

		if (!$helperSet) 
		{
			$helperSet = new \Symfony\Component\Console\Helper\HelperSet;
			$helperSet->set(new \Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper($container->documentManager), 'dm');
			$helperSet->set(new \Symfony\Component\Console\Helper\DialogHelper, 'dialog');
		}

		$console->setHelperSet($helperSet);
		$console->setCatchExceptions(FALSE);

		$commands = array();
		foreach (array_keys($container->findByTag('consoleCommand')) as $name) {
			$commands[] = $container->getService($name);
		}
		$console->addCommands($commands);

		return $console;
	}

}