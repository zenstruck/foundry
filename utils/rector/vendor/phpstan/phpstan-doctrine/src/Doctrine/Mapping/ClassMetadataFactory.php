<?php declare(strict_types = 1);

namespace PHPStan\Doctrine\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use function class_exists;
use function count;
use const PHP_VERSION_ID;

class ClassMetadataFactory extends \Doctrine\ORM\Mapping\ClassMetadataFactory
{

	/** @var string */
	private $tmpDir;

	public function __construct(string $tmpDir)
	{
		$this->tmpDir = $tmpDir;
	}

	protected function initialize(): void
	{
		$drivers = [];
		if (class_exists(AnnotationReader::class)) {
			$docParser = new DocParser();
			$docParser->setIgnoreNotImportedAnnotations(true);
			$drivers[] = new AnnotationDriver(new AnnotationReader($docParser));
		}
		if (class_exists(AttributeDriver::class) && PHP_VERSION_ID >= 80000) {
			$drivers[] = new AttributeDriver([]);
		}

		$config = new Configuration();
		$config->setMetadataDriverImpl(count($drivers) === 1 ? $drivers[0] : new MappingDriverChain($drivers));
		$config->setAutoGenerateProxyClasses(true);
		$config->setProxyDir($this->tmpDir);
		$config->setProxyNamespace('__PHPStanDoctrine__\\Proxy');
		$connection = DriverManager::getConnection([
			'driver' => 'pdo_sqlite',
			'memory' => true,
		], $config);

		$em = EntityManager::create($connection, $config);
		$this->setEntityManager($em);
		parent::initialize();

		$this->initialized = true;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return ClassMetadata<T>
	 */
	protected function newClassMetadataInstance($className)
	{
		return new ClassMetadata($className);
	}

}
