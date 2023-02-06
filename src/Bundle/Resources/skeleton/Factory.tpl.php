<?php /** @var \Zenstruck\Foundry\Bundle\Maker\Factory\MakeFactoryData $makeFactoryData */?><?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?php
foreach ($makeFactoryData->getUses() as $use) {
    echo "use $use;\n";
}
?>

/**
 * @extends ModelFactory<<?= $makeFactoryData->getObjectShortName() ?>>
 *
<?php
foreach ($makeFactoryData->getMethodsPHPDoc() as $methodPHPDoc) {
    echo "{$methodPHPDoc->toString()}\n";
}

if ($makeFactoryData->hasStaticAnalysisTool()) {
    echo " *\n";

    foreach ($makeFactoryData->getMethodsPHPDoc() as $methodPHPDoc) {
        echo "{$methodPHPDoc->toString($makeFactoryData->staticAnalysisTool())}\n";
    }
}
?>
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
foreach ($makeFactoryData->getDefaultProperties() as $propertyName => $value) {
    echo "            '{$propertyName}' => {$value}\n";
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
<?php if (!$makeFactoryData->isPersisted()): ?>
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
