<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use <?= $entity->getName() ?>;
<?php if ($repository): ?>use <?= $repository->getName() ?>;
use Zenstruck\Foundry\RepositoryProxy;
<?php endif ?>
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @method static <?= $entity->getShortName() ?>|Proxy findOrCreate(array $attributes)
 * @method static <?= $entity->getShortName() ?>|Proxy random()
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] randomSet(int $number)
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] randomRange(int $min, int $max)
<?php if ($repository): ?> * @method static <?= $repository->getShortName() ?>|RepositoryProxy repository()
<?php endif ?>
 * @method <?= $entity->getShortName() ?>|Proxy create($attributes = [])
 * @method <?= $entity->getShortName() ?>[]|Proxy[] createMany(int $number, $attributes = [])
 */
final class <?= $class_name ?> extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://github.com/zenstruck/foundry#model-factories)
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->beforeInstantiate(function(<?= $entity->getShortName() ?> $<?= \lcfirst($entity->getShortName()) ?>) {})
        ;
    }

    protected static function getClass(): string
    {
        return <?= $entity->getShortName() ?>::class;
    }
}
