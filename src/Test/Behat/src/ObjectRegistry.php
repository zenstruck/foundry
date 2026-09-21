<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat;

use Symfony\Component\Uid\AbstractUid;
use Zenstruck\Foundry\Persistence\IdentifierResolver;
use Zenstruck\Foundry\Story\Event\StateAddedToStory;
use Zenstruck\Foundry\Test\Behat\Exception\CompositeIdentifierNotSupported;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectAlreadyRegistered;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectNotFound;

use function Zenstruck\Foundry\Persistence\repository;

/**
 * Registry of the named objects created during a Behat exercise.
 *
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ObjectRegistry
{
    /**
     * We need to use static properties in order that this is kept between kernel resets.
     */

    /** @var array<class-string, array<string, object>> */
    private static array $objects = [];

    /**
     * The StateAddedToStory listener is compiled into any test-env container where
     * FriendsOfBehatSymfonyExtensionBundle is registered ("behat.service_container" is a synthetic
     * definition, present even when the kernel is booted by PHPUnit). Story states must only be
     * captured while a Behat exercise is running: BootConfigurationListener flips this flag.
     */
    private static bool $capturingStoryStates = false;

    /**
     * In reset modes where the registry survives across scenarios (feature, manual, disabled),
     * a Background block re-creates its named objects before every scenario: re-registering a
     * name from a previous scenario replaces it, while a duplicate within the same scenario
     * remains an error.
     *
     * @var array<class-string, array<string, true>>
     */
    private static array $namesInCurrentScenario = [];

    public function __construct(
        private readonly FactoryShortNameResolver $factoryShortNameResolver,
        private readonly IdentifierResolver $persistenceManager,
    ) {
    }

    /**
     * @param StateAddedToStory<object> $event
     */
    public function storeAfterStateAddedToStory(StateAddedToStory $event): void
    {
        if (!self::$capturingStoryStates) {
            return;
        }

        $this->store($event->object, $event->name);
    }

    public static function startCapturingStoryStates(): void
    {
        self::$capturingStoryStates = true;
    }

    public static function stopCapturingStoryStates(): void
    {
        self::$capturingStoryStates = false;
    }

    public function store(object $object, string $objectName): void
    {
        if (isset(self::$namesInCurrentScenario[$object::class][$objectName])) {
            throw ObjectAlreadyRegistered::forClassAndName($object::class, $objectName);
        }

        self::$namesInCurrentScenario[$object::class][$objectName] = true;
        self::$objects[$object::class][$objectName] = $object;
    }

    public static function startScenario(): void
    {
        self::$namesInCurrentScenario = [];
    }

    /**
     * @param class-string $objectClass
     */
    public function has(string $objectClass, string $objectName): bool
    {
        return isset(self::$objects[$objectClass][$objectName]);
    }

    public function getByFactoryShortName(string $factoryShortName, string $objectName): object
    {
        $objectClass = $this->factoryShortNameResolver->targetObjectClassFor($factoryShortName);

        if (!$this->has($objectClass, $objectName)) {
            throw ObjectNotFound::forFactoryAndName($factoryShortName, $objectName);
        }

        return self::$objects[$objectClass][$objectName];
    }

    /**
     * @param class-string $objectClass
     *
     * @throws ObjectNotFound
     */
    public function getByObjectClass(string $objectClass, string $objectName): object
    {
        if (!$this->has($objectClass, $objectName)) {
            throw ObjectNotFound::forClassAndName($objectClass, $objectName);
        }

        return self::$objects[$objectClass][$objectName];
    }

    public function reset(): void
    {
        self::$objects = [];
        self::$namesInCurrentScenario = [];
    }

    /**
     * Resolved from the database (the row with the highest id): also sees rows created
     * by the application under test, not only the ones persisted by Foundry.
     */
    public function lastIdFor(string $factoryShortName): int|string
    {
        return $this->coerceIdToScalar(
            $this->persistenceManager->getIdentifierValues(self::initialize($this->lastObjectFor($factoryShortName)))
        );
    }

    /**
     * @throws CompositeIdentifierNotSupported
     */
    public function lastObjectFor(string $factoryShortName): object
    {
        $objectClass = $this->factoryShortNameResolver->targetObjectClassFor($factoryShortName);

        // validate the sort field first: composite identifiers must be rejected as a pure domain
        // error, before repository() (which requires a booted Foundry) is evaluated as the receiver
        $sortField = $this->identifierSortFieldFor($objectClass);

        return repository($objectClass)->lastOrFail($sortField);
    }

    public function idFor(string $factoryShortName, string $objectName): int|string
    {
        $object = self::initialize($this->getByFactoryShortName($factoryShortName, $objectName));

        return $this->coerceIdToScalar(
            $this->persistenceManager->getIdentifierValues($object)
        );
    }

    /**
     * Replaces every <foundry:lastId(factory)> and <foundry:id(factory, name)> placeholder in the given string.
     */
    public function resolveIdPlaceholders(string $value): string
    {
        $resolved = \preg_replace_callback(
            '/<foundry:(?:lastId\(\s*(?<lastIdFactory>[^)]+?)\s*\)|id\(\s*(?<factory>[^,)]+?)\s*,\s*(?<name>[^)]+?)\s*\))>/',
            function(array $matches): string {
                try {
                    return (string) ('' !== ($matches['factory'] ?? '')
                        ? $this->idFor(self::unquote($matches['factory']), self::unquote($matches['name']))
                        : $this->lastIdFor(self::unquote($matches['lastIdFactory'])));
                } catch (CompositeIdentifierNotSupported $e) {
                    throw new \InvalidArgumentException("The \"{$matches[0]}\" placeholder cannot be resolved: {$e->getMessage()}", previous: $e);
                }
            },
            $value
        ) ?? $value;

        if (\preg_match('/<foundry:(?:lastId(?:\([^)]*\))?|id\([^)]*\))>/', $resolved, $matches)) {
            throw new \InvalidArgumentException("Malformed id placeholder \"{$matches[0]}\": expected <foundry:lastId(factory)> or <foundry:id(factory, name)>.");
        }

        return $resolved;
    }

    /**
     * Uninitialized lazy ghosts (e.g. reset by the PersistedObjectsTracker) read as null
     * identifiers through raw reflection: initialize them before reading anything.
     */
    private static function initialize(object $object): object
    {
        return ($reflector = new \ReflectionClass($object))->isUninitializedLazyObject($object)
            ? $reflector->initializeLazyObject($object)
            : $object;
    }

    public function isStored(object $object): bool
    {
        return array_any(
            self::$objects[$object::class] ?? [],
            static fn(object $o) => $o === $object
        );
    }

    public function getNameFor(object $object): string
    {
        return array_find_key(
            self::$objects[$object::class] ?? [],
            static fn(object $o) => $o === $object
        ) ?? throw new \LogicException('Object is not stored in the registry.');
    }

    /**
     * The identifier field is not necessarily named "id" (e.g. a Uuid $uuid property). Composite
     * identifiers are rejected: "the last row" has no single column to sort on, mirroring the
     * "exactly one identifier" invariant enforced on values by coerceIdToScalar(). The caller
     * (placeholder-aware) decorates the exception with the offending placeholder.
     *
     * @param class-string $objectClass
     *
     * @throws CompositeIdentifierNotSupported
     */
    private function identifierSortFieldFor(string $objectClass): string
    {
        $fields = $this->persistenceManager->getIdentifierFields($objectClass);

        if (1 !== \count($fields)) {
            throw CompositeIdentifierNotSupported::forClass($objectClass, $fields);
        }

        return $fields[0];
    }

    private static function unquote(string $value): string
    {
        return \trim($value, '"');
    }

    /**
     * @param array<string, mixed> $ids
     */
    private function coerceIdToScalar(array $ids): int|string
    {
        if (1 !== \count($ids)) {
            throw new \InvalidArgumentException(\sprintf('Cannot resolve the id: the entity must have exactly one identifier value, got %d ("%s").', \count($ids), \implode('", "', \array_keys($ids))));
        }

        $id = array_first($ids);

        if ($id instanceof AbstractUid) {
            return $id->toRfc4122();
        }

        if (!\is_int($id) && !\is_string($id)) {
            throw new \InvalidArgumentException(\sprintf('Wrong type for the id: expected int, string or Uid, got "%s".', \get_debug_type($id)));
        }

        return $id;
    }
}
