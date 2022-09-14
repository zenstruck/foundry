<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use <?= $entity->getName() ?>;
<?php if ($repository): ?>use <?= $repository->getName() ?>;
use Zenstruck\Foundry\RepositoryProxy;
<?php endif ?>
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<<?= $entity->getShortName() ?>>
 *
 * @method static <?= $entity->getShortName() ?>|Proxy createOne(array $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] createSequence(array|callable $sequence)
 * @method static <?= $entity->getShortName() ?>|Proxy find(object|array|mixed $criteria)
 * @method static <?= $entity->getShortName() ?>|Proxy findOrCreate(array $attributes)
 * @method static <?= $entity->getShortName() ?>|Proxy first(string $sortedField = 'id')
 * @method static <?= $entity->getShortName() ?>|Proxy last(string $sortedField = 'id')
 * @method static <?= $entity->getShortName() ?>|Proxy random(array $attributes = [])
 * @method static <?= $entity->getShortName() ?>|Proxy randomOrCreate(array $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] all()
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] findBy(array $attributes)
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
<?php if ($repository): ?> * @method static <?= $repository->getShortName() ?>|RepositoryProxy repository()
<?php endif ?>
 * @method <?= $entity->getShortName() ?>|Proxy create(array|callable $attributes = [])
 */
final class <?= $class_name ?> extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
<?php
foreach ($defaultProperties as $fieldname => $type) {
        echo "            '".$fieldname."' => ".$type."\n";
}
?>
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(<?= $entity->getShortName() ?> $<?= \lcfirst($entity->getShortName()) ?>): void {})
        ;
    }

    protected static function getClass(): string
    {
        return <?= $entity->getShortName() ?>::class;
    }
}
