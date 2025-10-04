<?php /** @var \Zenstruck\Foundry\Maker\Factory\MakeFactoryData $makeFactoryData */ ?><?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

<?php
foreach ($makeFactoryData->getUses() as $use) {
    echo "use {$use};\n";
}
?>

/**
 * @extends <?php echo $makeFactoryData->getFactoryClassShortName(); ?><<?php echo $makeFactoryData->getObjectShortName(); ?>>
<?php
if (count($makeFactoryData->getMethodsPHPDoc())) {
    echo " *\n";
    foreach ($makeFactoryData->getMethodsPHPDoc() as $methodPHPDoc) {
        echo "{$methodPHPDoc->toString()}\n";
    }

    echo " *\n";

    foreach ($makeFactoryData->getMethodsPHPDoc() as $methodPHPDoc) {
        echo "{$methodPHPDoc->toString($makeFactoryData->staticAnalysisTool())}\n";
    }
}
?>
 */
final class <?php echo $class_name; ?> extends <?php echo $makeFactoryData->getFactoryClassShortName(); ?>
{
<?php if ($makeFactoryData->shouldAddHints()): ?>    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

<?php endif ?><?php if ($makeFactoryData->shouldAddOverrideAttributes()): ?>    #[\Override]<?php endif ?>
    public static function class(): string
    {
        return <?php echo $makeFactoryData->getObjectShortName(); ?>::class;
    }

<?php if ($makeFactoryData->shouldAddHints()): ?>        /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
<?php endif ?><?php if ($makeFactoryData->shouldAddOverrideAttributes()): ?>    #[\Override]<?php endif ?>
    protected function defaults(): array<?php if ($makeFactoryData->shouldAddHints()): ?>|callable<?php endif ?>
    {
        return [
<?php
foreach ($makeFactoryData->getDefaultProperties() as $propertyName => $value) {
    echo "            '{$propertyName}' => {$value}\n";
}
?>
        ];
    }

<?php if ($makeFactoryData->shouldAddHints()): ?>        /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
<?php if ($makeFactoryData->shouldAddOverrideAttributes()): ?>    #[\Override]<?php endif ?>
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(<?php echo $makeFactoryData->getObjectShortName(); ?> $<?php echo \lcfirst($makeFactoryData->getObjectShortName()); ?>): void {})
        ;
    }
<?php endif ?>}
