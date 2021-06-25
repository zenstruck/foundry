<?php

namespace Zenstruck\Foundry\Bundle\Maker;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Zenstruck\Foundry\ModelFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MakeFactory extends AbstractMaker
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var string[] */
    private $entitiesWithFactories;

    public function __construct(ManagerRegistry $managerRegistry, \Traversable $factories)
    {
        $this->managerRegistry = $managerRegistry;
        $this->entitiesWithFactories = \array_map(
            static function(ModelFactory $factory) {
                return $factory::getEntityClass();
            },
            \iterator_to_array($factories)
        );
    }

    public static function getCommandName(): string
    {
        return 'make:factory';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a Foundry model factory for a Doctrine entity class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('entity', InputArgument::OPTIONAL, 'Entity class to create a factory for')
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Customize the namespace for generated factories', 'Factory')
            ->addOption('test', null, InputOption::VALUE_NONE, 'Create in <fg=yellow>tests/</> instead of <fg=yellow>src/</>')
        ;

        $inputConfig->setArgumentAsNonInteractive('entity');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('entity')) {
            return;
        }

        if (!$input->getOption('test')) {
            $io->text('// Note: pass <fg=yellow>--test</> if you want to generate factories in your <fg=yellow>tests/</> directory');
            $io->newLine();
        }

        $argument = $command->getDefinition()->getArgument('entity');
        $entity = $io->choice($argument->getDescription(), $this->entityChoices());

        $input->setArgument('entity', $entity);
    }

    private function getNamespacePrefix(string $namespace, bool $isTest) : string
    {
        $appPos = strpos($namespace, 'App\\');
        if ($appPos !== false && $appPos <= 1) {
            $namespace = substr($namespace,$appPos+4);
        }
        if ($isTest && strpos($namespace, 'Tests\\') === false) {
            $namespace = 'Tests\\'.$namespace;
        }
        return $namespace;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $class = $input->getArgument('entity');

        if (!\class_exists($class)) {
            $class = $generator->createClassNameDetails($class, 'Entity\\')->getFullName();
        }

        if (!\class_exists($class)) {
            throw new RuntimeCommandException(\sprintf('Entity "%s" not found.', $input->getArgument('entity')));
        }

        $entity = new \ReflectionClass($class);
        $factory = $generator->createClassNameDetails(
            $entity->getShortName(),
            $this->getNamespacePrefix($input->getOption('namespace'),$input->getOption('test')),
            'Factory'
        );

        $repository = new \ReflectionClass($this->managerRegistry->getRepository($entity->getName()));

        if (0 !== \mb_strpos($repository->getName(), $generator->getRootNamespace())) {
            // not using a custom repository
            $repository = null;
        }

        $generator->generateClass(
            $factory->getFullName(),
            __DIR__.'/../Resources/skeleton/Factory.tpl.php',
            [
                'entity' => $entity,
                'repository' => $repository,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new factory and set default values/states.',
            'Find the documentation at https://github.com/zenstruck/foundry#model-factories',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        // noop
    }

    private function entityChoices(): array
    {
        $choices = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                if (!\in_array($metadata->getName(), $this->entitiesWithFactories, true)) {
                    $choices[] = $metadata->getName();
                }
            }
        }

        \sort($choices);

        if (empty($choices)) {
            throw new RuntimeCommandException('No entities found.');
        }

        return $choices;
    }
}
