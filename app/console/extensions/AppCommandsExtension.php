<?php
namespace Extensions;

use Nette\Config\Configurator;

/**
 * Description of AppCommandsExtension
 *
 * @author Martin
 */
class AppCommandsExtension extends \Nette\Config\CompilerExtension
{
	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		// console commands - ODM
		$container->addDefinition($this->prefix('consoleCommandAppCreateUser'))
			->setClass('Console\Command\CreateUser')
			->addTag('consoleCommand');
	}
}