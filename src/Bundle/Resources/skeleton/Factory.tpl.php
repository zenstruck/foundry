<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use <?= $object->getName() ?>;
<?php if ($persisted && $repository): ?>use <?= $repository->getName() ?>;
use Zenstruck\Foundry\RepositoryProxy;
<?php endif ?>
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<<?= $object->getShortName() ?>>
 *
 * @method <?= $object->getShortName() ?>|Proxy create(array|callable $attributes = [])
 * @method static <?= $object->getShortName() ?>|Proxy createOne(array $attributes = [])
<?php if ($persisted): ?> * @method static <?= $object->getShortName() ?>|Proxy find(object|array|mixed $criteria)
 * @method static <?= $object->getShortName() ?>|Proxy findOrCreate(array $attributes)
 * @method static <?= $object->getShortName() ?>|Proxy first(string $sortedField = 'id')
 * @method static <?= $object->getShortName() ?>|Proxy last(string $sortedField = 'id')
 * @method static <?= $object->getShortName() ?>|Proxy random(array $attributes = [])
 * @method static <?= $object->getShortName() ?>|Proxy randomOrCreate(array $attributes = [])
 * @method static <?= $object->getShortName() ?>[]|Proxy[] all()
<?php endif ?>
 * @method static <?= $object->getShortName() ?>[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static <?= $object->getShortName() ?>[]|Proxy[] createSequence(array|callable $sequence)
<?php if ($persisted): ?> * @method static <?= $object->getShortName() ?>[]|Proxy[] findBy(array $attributes)
 * @method static <?= $object->getShortName() ?>[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static <?= $object->getShortName() ?>[]|Proxy[] randomSet(int $number, array $attributes = [])
<?php if ($repository): ?> * @method static <?= $repository->getShortName() ?>|RepositoryProxy repository()
<?php endif ?>
<?php endif ?>
<?php if ($phpstanEnabled): ?> *
 * @phpstan-method Proxy<<?= $object->getShortName() ?>> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<<?= $object->getShortName() ?>> createOne(array $attributes = [])
<?php if ($persisted): ?> * @phpstan-method static Proxy<<?= $object->getShortName() ?>> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<<?= $object->getShortName() ?>> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<<?= $object->getShortName() ?>> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<<?= $object->getShortName() ?>> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<<?= $object->getShortName() ?>> random(array $attributes = [])
 * @phpstan-method static Proxy<<?= $object->getShortName() ?>> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Proxy<<?= $object->getShortName() ?>>> all()
<?php endif ?>
 * @phpstan-method static list<Proxy<<?= $object->getShortName() ?>>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<<?= $object->getShortName() ?>>> createSequence(array|callable $sequence)
<?php if ($persisted): ?>
 * @phpstan-method static list<Proxy<<?= $object->getShortName() ?>>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<<?= $object->getShortName() ?>>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<<?= $object->getShortName() ?>>> randomSet(int $number, array $attributes = [])
<?php if ($repository): ?> * @phpstan-method static RepositoryProxy<<?= $repository->getShortName() ?>> repository()
<?php endif ?>
<?php endif ?>
<?php endif ?>
 */
final class <?= $class_name ?> extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
<?php
foreach ($defaultProperties as $fieldname => $type) {
        echo "            '".$fieldname."' => ".$type."\n";
}
?>
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
<?php if (!$persisted): ?>
            ->withoutPersisting()
<?php endif ?>
            // ->afterInstantiate(function(<?= $object->getShortName() ?> $<?= lcfirst($object->getShortName()) ?>): void {})
        ;
    }

    protected static function getClass(): string
    {
        return <?= $object->getShortName() ?>::class;
    }
}
