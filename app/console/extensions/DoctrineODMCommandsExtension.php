<?php
namespace Extensions;

/**
 * Console service.
 *
 * @author Martin Bažík
 */
class DoctrineODMCommandsExtension extends \Nette\Config\CompilerExtension
{
	/**
	 * Processes configuration data
	 *
	 * @return void
	 */
	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();

		// console commands - ODM
		$container->addDefinition($this->prefix('consoleCommandODMClearMetadataCache'))
			->setClass('Doctrine\ODM\MongoDB\Tools\Console\Command\ClearCache\MetadataCommand')
			->addTag('consoleCommand');
		
		$container->addDefinition($this->prefix('consoleCommandODMCreateSchema'))
			->setClass('Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand')
			->addTag('consoleCommand');
		
		$container->addDefinition($this->prefix('consoleCommandODMDropSchema'))
			->setClass('Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\DropCommand')
			->addTag('consoleCommand');
		
		$container->addDefinition($this->prefix('consoleCommandODMGenerateDocuments'))
			->setClass('Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateDocumentsCommand')
			->addTag('consoleCommand');
		
		$container->addDefinition($this->prefix('consoleCommandODMGenerateHydrators'))
			->setClass('Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateHydratorsCommand')
			->addTag('consoleCommand');
		
		$container->addDefinition($this->prefix('consoleCommandODMGenerateProxies'))
			->setClass('Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateProxiesCommand')
			->addTag('consoleCommand');
		
		$container->addDefinition($this->prefix('consoleCommandODMGenerateRepositories'))
			->setClass('Doctrine\ODM\MongoDB\Tools\Console\Command\GenerateRepositoriesCommand')
			->addTag('consoleCommand');
		
		$container->addDefinition($this->prefix('consoleCommandODMQuery'))
			->setClass('Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand')
			->addTag('consoleCommand');
	}
}