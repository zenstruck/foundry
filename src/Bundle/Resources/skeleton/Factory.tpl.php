<?php /** @var \Zenstruck\Foundry\Bundle\Maker\MakeFactoryData $makeFactoryData */?><?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $makeFactoryData->renderUses() ?>

/**
 * @extends ModelFactory<<?= $makeFactoryData->getObjectShortName() ?>>
 *
 * @method        <?= $makeFactoryData->getObjectShortName() ?>|Proxy     create(array|callable $attributes = [])
 * @method static <?= $makeFactoryData->getObjectShortName() ?>|Proxy     createOne(array $attributes = [])
<?php if ($persisted): ?> * @method static <?= $makeFactoryData->getObjectShortName() ?>|Proxy     find(object|array|mixed $criteria)
 * @method static <?= $makeFactoryData->getObjectShortName() ?>|Proxy     findOrCreate(array $attributes)
 * @method static <?= $makeFactoryData->getObjectShortName() ?>|Proxy     first(string $sortedField = 'id')
 * @method static <?= $makeFactoryData->getObjectShortName() ?>|Proxy     last(string $sortedField = 'id')
 * @method static <?= $makeFactoryData->getObjectShortName() ?>|Proxy     random(array $attributes = [])
 * @method static <?= $makeFactoryData->getObjectShortName() ?>|Proxy     randomOrCreate(array $attributes = [])
 * @method static <?= $makeFactoryData->getObjectShortName() ?>[]|Proxy[] all()
<?php endif ?>
 * @method static <?= $makeFactoryData->getObjectShortName() ?>[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static <?= $makeFactoryData->getObjectShortName() ?>[]|Proxy[] createSequence(array|callable $sequence)
<?php if ($persisted): ?> * @method static <?= $makeFactoryData->getObjectShortName() ?>[]|Proxy[] findBy(array $attributes)
 * @method static <?= $makeFactoryData->getObjectShortName() ?>[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static <?= $makeFactoryData->getObjectShortName() ?>[]|Proxy[] randomSet(int $number, array $attributes = [])
<?php if ($makeFactoryData->getRepositoryShortName()): ?> * @method static <?= $makeFactoryData->getRepositoryShortName() ?>|RepositoryProxy repository()
<?php endif ?>
<?php endif ?>
<?php if ($phpstanEnabled): ?> *
 * @phpstan-method        Proxy<<?= $makeFactoryData->getObjectShortName() ?>>       create(array|callable $attributes = [])
 * @phpstan-method static Proxy<<?= $makeFactoryData->getObjectShortName() ?>>       createOne(array $attributes = [])
<?php if ($persisted): ?> * @phpstan-method static Proxy<<?= $makeFactoryData->getObjectShortName() ?>>       find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<<?= $makeFactoryData->getObjectShortName() ?>>       findOrCreate(array $attributes)
 * @phpstan-method static Proxy<<?= $makeFactoryData->getObjectShortName() ?>>       first(string $sortedField = 'id')
 * @phpstan-method static Proxy<<?= $makeFactoryData->getObjectShortName() ?>>       last(string $sortedField = 'id')
 * @phpstan-method static Proxy<<?= $makeFactoryData->getObjectShortName() ?>>       random(array $attributes = [])
 * @phpstan-method static Proxy<<?= $makeFactoryData->getObjectShortName() ?>>       randomOrCreate(array $attributes = [])
 * @phpstan-method static list<Proxy<<?= $makeFactoryData->getObjectShortName() ?>>> all()
<?php endif ?>
 * @phpstan-method static list<Proxy<<?= $makeFactoryData->getObjectShortName() ?>>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<<?= $makeFactoryData->getObjectShortName() ?>>> createSequence(array|callable $sequence)
<?php if ($persisted): ?>
 * @phpstan-method static list<Proxy<<?= $makeFactoryData->getObjectShortName() ?>>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<<?= $makeFactoryData->getObjectShortName() ?>>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<<?= $makeFactoryData->getObjectShortName() ?>>> randomSet(int $number, array $attributes = [])
<?php if ($makeFactoryData->getRepositoryShortName()): ?> * @phpstan-method static RepositoryProxy<<?= $makeFactoryData->getRepositoryShortName() ?>> repository()
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
<?= $makeFactoryData->renderDefaultProperties() ?>
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
            // ->afterInstantiate(function(<?= $makeFactoryData->getObjectShortName() ?> $<?= lcfirst($makeFactoryData->getObjectShortName()) ?>): void {})
        ;
    }

    protected static function getClass(): string
    {
        return <?= $makeFactoryData->getObjectShortName() ?>::class;
    }
}
