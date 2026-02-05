<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Transformation\Transform;
use Zenstruck\Foundry\Factory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @phpstan-import-type Parameters from Factory
 *
 * @internal
 * @final
 */
class FoundryContext extends AbstractFoundryContext implements Context
{
    #[\Override]
    #[Given('there is a(n) :factoryShortName')]
    #[Given('there is a(n) :factoryShortName :objectName')]
    #[Given('there is a(n) :factoryShortName called :objectName')]
    #[Given('there is a(n) :factoryShortName named :objectName')]
    public function createObject(string $factoryShortName, ?string $objectName = null): void
    {
        parent::createObject($factoryShortName, $objectName);
    }

    #[\Override]
    #[Given('there is a(n) :factoryShortName with')]
    #[Given('there is a(n) :factoryShortName :objectName with')]
    #[Given('there is a(n) :factoryShortName called :objectName with')]
    #[Given('there is a(n) :factoryShortName named :objectName with')]
    public function createObjectWithProperties(TableNode $table, string $factoryShortName, ?string $objectName = null): void
    {
        parent::createObjectWithProperties($table, $factoryShortName, $objectName);
    }

    #[\Override]
    #[Given('there are :factoryShortName with')]
    public function createObjectsWithProperties(TableNode $table, string $factoryShortName): void
    {
        parent::createObjectsWithProperties($table, $factoryShortName);
    }

    #[\Override]
    #[Then('/^(\d+) "([^"]*)" should exist$/')]
    #[Then('/^(\d+) ([^"]*) should exist$/')]
    public function assertNbObjectsExist(int $nb, string $factoryShortName): void
    {
        parent::assertNbObjectsExist($nb, $factoryShortName);
    }

    /**
     * Captures:
     *  - factoryShortName: quoted or unquoted factory/entity name
     *  - objectName: quoted or unquoted object reference
     *
     * Supports optional articles ("the", "a", "an"), optional "called"/"named",
     * optional "exist and", and optional trailing "properties".
     */
    #[\Override]
    #[Then('/^(?:the |an? )?(?!\d)(?|"(?P<factoryShortName>[^"]+)"|(?P<factoryShortName>\S+)) (?:(?:called |named ))?(?|"(?P<objectName>[^"]+)"|(?P<objectName>\S+)) should (?:exist and )?have(?: properties)?$/')]
    public function assertObjectHasProperties(FoundryTableNode $table, string $factoryShortName, string $objectName): void
    {
        parent::assertObjectHasProperties($table, $factoryShortName, $objectName);
    }

    #[\Override]
    #[Then('/^(?:the |an? )?(?!\d)(?|"(?P<factoryShortName>[^"]+)"|(?P<factoryShortName>\S+)) (?:(?:called |named ))?(?|"(?P<objectName>[^"]+)"|(?P<objectName>\S+)) should exist$/')]
    public function assertObjectExists(string $factoryShortName, string $objectName): void
    {
        parent::assertObjectExists($factoryShortName, $objectName);
    }

    #[\Override]
    #[Then('/^(?:the |an? )?(?!\d)(?|"(?P<factoryShortName>[^"]+)"|(?P<factoryShortName>\S+)) (?:(?:called |named ))?(?|"(?P<objectName>[^"]+)"|(?P<objectName>\S+)) should not exist$/')]
    public function assertObjectDoesNotExist(string $factoryShortName, string $objectName): void
    {
        parent::assertObjectDoesNotExist($factoryShortName, $objectName);
    }

    #[\Override]
    #[Transform('/(.*)<lastId>(.*)/')]
    public function transformLastId(string $before, string $after): string
    {
        return parent::transformLastId($before, $after);
    }

    #[\Override]
    #[Transform('/(.*)<lastId\((.*)\)>(.*)/')]
    public function transformLastIdForSpecificObject(string $before, string $factoryShortName, string $after): string
    {
        return parent::transformLastIdForSpecificObject($before, $factoryShortName, $after);
    }

    #[\Override]
    #[Transform('/(.*)<id\((.*), (.*)\)>(.*)/')]
    public function transformIdForSpecificObject(string $before, string $factoryShortName, string $objectName, string $after): string
    {
        return parent::transformIdForSpecificObject($before, $factoryShortName, $objectName, $after);
    }
}
