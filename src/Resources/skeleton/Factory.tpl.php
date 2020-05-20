<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use <?= $entity->getName() ?>;
<?php if ($repository): ?>use <?= $repository->getName() ?>;
use Zenstruck\Foundry\RepositoryProxy;
<?php endif ?>
use Zenstruck\Foundry\CustomFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @method static <?= $entity->getShortName() ?> make($attributes = [])
 * @method static <?= $entity->getShortName() ?>[] makeMany(int $number, $attributes = [])
 * @method static <?= $entity->getShortName() ?>|Proxy create($attributes = [], ?bool $proxy = null)
 * @method static <?= $entity->getShortName() ?>[]|Proxy[] createMany(int $number, $attributes = [], ?bool $proxy = null)
<?php if ($repository): ?> * @method static <?= $repository->getShortName() ?>|RepositoryProxy repository(bool $proxy = true)
<?php endif ?>
 * @method <?= $entity->getShortName() ?> instantiate($attributes = [])
 * @method <?= $entity->getShortName() ?>[] instantiateMany(int $number, $attributes = [])
 * @method <?= $entity->getShortName() ?>|Proxy persist($attributes = [], ?bool $proxy = null)
 * @method <?= $entity->getShortName() ?>[]|Proxy[] persistMany(int $number, $attributes = [], ?bool $proxy = null)
 */
final class <?= $class_name ?> extends CustomFactory
{
    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (link to docs)
        ];
    }

    protected static function getClass(): string
    {
        return <?= $entity->getShortName() ?>::class;
    }
}
