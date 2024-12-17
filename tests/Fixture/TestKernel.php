<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture;

use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zenstruck\Foundry\ORM\ResetDatabase\ResetDatabaseMode;
use Zenstruck\Foundry\Tests\Fixture\Factories\ArrayFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Stories\ServiceStory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestKernel extends FoundryTestKernel
{
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        yield new MakerBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $c->loadFromExtension('zenstruck_foundry', [
            'orm' => [
                'reset' => [
                    'mode' => ResetDatabaseMode::SCHEMA,
                ],
            ],
        ]);

        $c->register(ArrayFactory::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(Object1Factory::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(ServiceStory::class)->setAutowired(true)->setAutoconfigured(true);
    }
}
