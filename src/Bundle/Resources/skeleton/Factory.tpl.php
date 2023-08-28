<?php /** @var \Zenstruck\Foundry\Bundle\Maker\Factory\MakeFactoryData $makeFactoryData */?><?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?php
foreach ($makeFactoryData->getUses() as $use) {
    echo "use $use;\n";
}
?>

/**
 * @extends <?= $makeFactoryData->baseFactoryClass() ?><<?= $makeFactoryData->getObjectShortName() ?>>
 *
<?php
foreach ($makeFactoryData->getMethodsPHPDoc() as $methodPHPDoc) {
    echo "{$methodPHPDoc->toString()}\n";
}
?>
 */
final class <?= $class_name ?> extends <?= $makeFactoryData->baseFactoryClass() ?>
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
foreach ($makeFactoryData->getDefaultProperties() as $propertyName => $value) {
    echo "            '{$propertyName}' => {$value}\n";
}
?>
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(<?= $makeFactoryData->getObjectShortName() ?> $<?= lcfirst($makeFactoryData->getObjectShortName()) ?>): void {})
        ;
    }

    public static function class(): string
    {
        return <?= $makeFactoryData->getObjectShortName() ?>::class;
    }
}
