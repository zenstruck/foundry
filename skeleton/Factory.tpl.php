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
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return <?php echo $makeFactoryData->getObjectShortName(); ?>::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
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
            // ->afterInstantiate(function(<?php echo $makeFactoryData->getObjectShortName(); ?> $<?php echo \lcfirst($makeFactoryData->getObjectShortName()); ?>): void {})
        ;
    }
}
