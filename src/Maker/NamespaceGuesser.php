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

namespace Zenstruck\Foundry\Maker;

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Str;
use Zenstruck\Foundry\Persistence\PersistenceManager;

/**
 * Guesses namespaces depending on:
 * - user input "--namespace": will be used as a prefix (after root namespace "App\")
 * - user input "--test": will add "Test\" just after root namespace
 * - doctrine mapping: if the original class is a doctrine object, will suffix the namespace relative to doctrine's mapping.
 *
 * @internal
 */
final class NamespaceGuesser
{
    /** @var list<string> */
    private array $doctrineNamespaces;

    public function __construct(?PersistenceManager $persistenceManager)
    {
        $this->doctrineNamespaces = $persistenceManager?->managedNamespaces() ?? [];
    }

    public function __invoke(Generator $generator, string $originalClass, string $baseNamespace, bool $test): string
    {
        // strip maker's root namespace if set
        $baseNamespace = $this->stripRootNamespace($baseNamespace, $generator->getRootNamespace());

        $doctrineBasedNamespace = $this->namespaceSuffixFromDoctrineMapping($originalClass);

        if ($doctrineBasedNamespace) {
            $baseNamespace = "{$baseNamespace}\\{$doctrineBasedNamespace}";
        }

        // if creating in tests dir, ensure namespace prefixed with Tests\
        if ($test && 0 !== \mb_strpos($baseNamespace, 'Tests\\')) {
            $baseNamespace = 'Tests\\'.$baseNamespace;
        }

        return $baseNamespace;
    }

    private function namespaceSuffixFromDoctrineMapping(string $originalClass): ?string
    {
        $originalClassNamespace = Str::getNamespace($originalClass);

        foreach ($this->doctrineNamespaces as $doctrineNamespace) {
            if (\str_starts_with($originalClassNamespace, $doctrineNamespace)) {
                return $this->stripRootNamespace($originalClassNamespace, $doctrineNamespace);
            }
        }

        return null;
    }

    private static function stripRootNamespace(string $class, string $rootNamespace): string
    {
        if (0 === \mb_strpos($class, $rootNamespace)) {
            $class = \mb_substr($class, \mb_strlen($rootNamespace));
        }

        return \trim($class, '\\');
    }
}
