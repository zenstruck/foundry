<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Mongo;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException as MongoMappingException;
use Doctrine\Persistence\Mapping\MappingException;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\PersistenceStrategy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @method DocumentManager       objectManagerFor(string $class)
 * @method list<DocumentManager> objectManagers()
 */
final class MongoPersistenceStrategy extends PersistenceStrategy
{
    public function contains(object $object): bool
    {
        $dm = $this->objectManagerFor($object::class);

        return $dm->contains($object) && !$dm->getUnitOfWork()->isScheduledForInsert($object);
    }

    public function hasChanges(object $object): bool
    {
        $dm = $this->objectManagerFor($object::class);

        if (!$dm->contains($object)) {
            return false;
        }

        // cannot use UOW::recomputeSingleEntityChangeSet() here as it wrongly computes embedded objects as changed
        $dm->getUnitOfWork()->computeChangeSet($dm->getClassMetadata($object::class), $object);

        return (bool) $dm->getUnitOfWork()->getDocumentChangeSet($object);
    }

    public function truncate(string $class): void
    {
        $this->objectManagerFor($class)->getDocumentCollection($class)->deleteMany([]);
    }

    public function managedNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->objectManagers() as $objectManager) {
            $namespaces[] = $objectManager->getConfiguration()->getDocumentNamespaces();
        }

        return \array_values(\array_merge(...$namespaces));
    }

    public function embeddablePropertiesFor(object $object, string $owner): ?array
    {
        try {
            $metadata = $this->objectManagerFor($owner)->getClassMetadata($object::class);
        } catch (MappingException|MongoMappingException) {
            return null;
        }

        if (!$metadata->isEmbeddedDocument) {
            return null;
        }

        $properties = [];

        foreach ($metadata->getFieldNames() as $field) {
            $properties[$field] = $metadata->getFieldValue($object, $field);
        }

        return $properties;
    }

    public function isEmbeddable(object $object): bool
    {
        return $this->objectManagerFor($object::class)->getClassMetadata($object::class)->isEmbeddedDocument;
    }

    public function resetDatabase(KernelInterface $kernel): void
    {
        // noop
    }

    public function resetSchema(KernelInterface $kernel): void
    {
        $application = self::application($kernel);

        foreach ($this->managers() as $manager) {
            try {
                self::runCommand(
                    $application,
                    'doctrine:mongodb:schema:drop',
                    [
                        '--dm' => $manager,
                    ]
                );
            } catch (\Exception) {
            }

            self::runCommand(
                $application,
                'doctrine:mongodb:schema:create',
                [
                    '--dm' => $manager,
                ]
            );
        }
    }

    /**
     * @return string[]
     */
    private function managers(): array
    {
        return $this->config['reset']['document_managers'];
    }
}
