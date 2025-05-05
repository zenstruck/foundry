<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\App\Command;

use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zenstruck\Foundry\Tests\Fixture\Document\GenericDocument;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

#[AsCommand(name: 'foundry:test:update-generic-model')]
final class UpdateGenericModelCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'service_container')]
        private readonly ContainerInterface $container,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('dbms', mode: InputArgument::REQUIRED);
        $this->addArgument('id', mode: InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ObjectManager $objectManager */
        [$objectManager, $class] = match ($input->getArgument('dbms')) {
            'orm' => [$this->container->get('doctrine.orm.entity_manager'), GenericEntity::class],
            'mongo' => [$this->container->get('doctrine_mongodb.odm.document_manager'), GenericDocument::class],
            default => throw new \RuntimeException('Invalid dbms'),
        };

        /** @var GenericModel $object */
        $object = $objectManager->find($class, $input->getArgument('id')) ?? throw new \RuntimeException('object not found');
        $object->setProp1('foo');
        $objectManager->flush();

        return 0;
    }
}
