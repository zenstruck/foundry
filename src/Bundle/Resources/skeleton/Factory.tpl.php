<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use <?= $entity->getName() ?>;
<?php if ($repository): ?>use <?= $repository->getName() ?>;
use Zenstruck\Foundry\RepositoryProxy;
<?php endif ?>
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @method static <?= $entity->getShortName() ?>|Proxy createOne(array $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static <?= $entity->getShortName() ?>|Proxy findOrCreate(array $attributes)
 * @method static <?= $entity->getShortName() ?>|Proxy random(array $attributes = [])
 * @method static <?= $entity->getShortName() ?>|Proxy randomOrCreate(array $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
<?php if ($repository): ?> * @method static <?= $repository->getShortName() ?>|RepositoryProxy repository()
<?php endif ?>
 * @method <?= $entity->getShortName() ?>|Proxy create($attributes = [])
 */
final class <?= $class_name ?> extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://github.com/zenstruck/foundry#factories-as-services)
    }

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
            // ->afterInstantiate(function(<?= $entity->getShortName() ?> $<?= \lcfirst($entity->getShortName()) ?>) {})
        ;
    }

    protected static function getClass(): string
    {
        return <?= $entity->getShortName() ?>::class;
    }
}
