<?php /** @var \Zenstruck\Foundry\Bundle\Maker\Factory\MakeFactoryData $makeFactoryData */ ?><?php echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

<?php
foreach ($makeFactoryData->getUses() as $use) {
    echo "use {$use};\n";
}
?>

/**
 * @extends PersistentProxyObjectFactory<<?php echo $makeFactoryData->getObjectShortName(); ?>>
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
final class <?php echo $class_name; ?> extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
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
    protected function initialize(): self
    {
        return $this
<?php if (!$makeFactoryData->isPersisted()) { ?>
            ->withoutPersisting()
<?php } ?>
            // ->afterInstantiate(function(<?php echo $makeFactoryData->getObjectShortName(); ?> $<?php echo \lcfirst($makeFactoryData->getObjectShortName()); ?>): void {})
        ;
    }

    protected static function getClass(): string
    {
        return <?php echo $makeFactoryData->getObjectShortName(); ?>::class;
    }
}
