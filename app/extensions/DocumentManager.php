<?php
namespace Extensions;

use Doctrine\Common\ClassLoader,
	Doctrine\Common\Annotations\AnnotationReader,
	Doctrine\MongoDB\Connection,
	Doctrine\ODM\MongoDB\Configuration,
	Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

/**
 * Description of DocumentManager
 *
 * @author Martin Bažík
 */
class DocumentManagerExtension extends \Nette\Config\CompilerExtension
{
	/**
	 * Processes configuration data
	 *
	 * @return void
	 */
	public function loadConfiguration()
	{
		
		$container = $this->getContainerBuilder();
		
		$config = $this->getConfig();
		
		// console application
		$container->addDefinition($this->prefix('documentManager'))
			->setClass('\Doctrine\ODM\MongoDB\DocumentManager')
			->setFactory('Extensions\DocumentManagerExtension::createDocumentManager', array('@container', $config))
			->setAutowired(FALSE);

		// aliases
		$container->addDefinition('documentManager')
			->setClass('\Doctrine\ODM\MongoDB\DocumentManager')
			->setFactory('@container::getService', array($this->prefix('documentManager')));
	}
	
	public static function createDocumentManager(\Nette\DI\Container $container, $config)
	{
		$params = $container->parameters;
		$configuration = new Configuration();
		
		$configuration->setProxyDir($config['proxyDir']);
		$configuration->setProxyNamespace('Proxies');

		$configuration->setHydratorDir($config['hydratorDir']);
		$configuration->setHydratorNamespace('Hydrators');
		
		$isProductionMode = $params['productionMode'];
		$configuration->setAutoGenerateHydratorClasses(!$isProductionMode);
		$configuration->setAutoGenerateProxyClasses(!$isProductionMode);

		$metadataCache = new $config['metaDataCacheClass'];
		$metadataCache->setNamespace($config['cachePrefix']);
		$configuration->setMetadataCacheImpl($metadataCache);

		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDORS_DIR . '/doctrine/mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php');

		$reader = new AnnotationReader();
		$driverImpl = new AnnotationDriver($reader, $config['documentsDir']);
		
		$configuration->setMetadataDriverImpl($driverImpl);

		$configuration->setDefaultDB($config['dbname']);

		$mongo = new \Mongo($config['uri'], array('connect' => true));
		$connection = new Connection($mongo);
		$dm = \Doctrine\ODM\MongoDB\DocumentManager::create($connection, $configuration);

		return $dm;
	}
	
	
}