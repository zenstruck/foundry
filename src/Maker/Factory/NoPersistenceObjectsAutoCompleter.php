<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Maker\Factory;

/**
 * @internal
 */
final class NoPersistenceObjectsAutoCompleter
{
    public function __construct(private string $kernelRootDir)
    {
    }

    /**
     * @return list<class-string>
     */
    public function getAutocompleteValues(): array
    {
        $classes = [];

        $excludedFiles = $this->excludedFiles();

        foreach ($this->getDefinedNamespaces() as $namespacePrefix => $rootFragment) {
            $allFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rootPath = "{$this->kernelRootDir}/{$rootFragment}"));

            /** @var \SplFileInfo $phpFile */
            foreach (new \RegexIterator($allFiles, '/\.php$/') as $phpFile) {
                if (\in_array($phpFile->getRealPath(), $excludedFiles, true)) {
                    continue;
                }

                $class = $this->toPSR4($rootPath, $phpFile, $namespacePrefix);

                if (\in_array($class, ['Zenstruck\Foundry\Proxy', 'Zenstruck\Foundry\RepositoryProxy', 'Zenstruck\Foundry\RepositoryAssertions'])) {
                    // do not load legacy Proxy: prevents deprecations in tests.
                    continue;
                }

                try {
                    // @phpstan-ignore-next-line $class is not always a class-string
                    $reflection = new \ReflectionClass($class);
                } catch (\Throwable) {
                    // remove all files which are not class / interface / traits
                    continue;
                }

                /** @var class-string $class */
                if (!$reflection->isInstantiable()) {
                    // remove abstract classes / interfaces / traits
                    continue;
                }

                $classes[] = $class;
            }
        }

        \sort($classes);

        return $classes;
    }

    private function toPSR4(string $rootPath, \SplFileInfo $fileInfo, string $namespacePrefix): string
    {
        // /app/src/Bundle/Maker/Factory/NoPersistenceObjectsAutoCompleter.php => /Bundle/Maker/Factory/NoPersistenceObjectsAutoCompleter
        $relativeFileNameWithoutExtension = \str_replace([$rootPath, '.php'], ['', ''], $fileInfo->getRealPath());

        return $namespacePrefix.\str_replace('/', '\\', $relativeFileNameWithoutExtension);
    }

    /**
     * @return array<string, string>
     */
    private function getDefinedNamespaces(): array
    {
        $composerConfig = $this->getComposerConfiguration();

        /** @var array<string, string> $definedNamespaces */
        $definedNamespaces = $composerConfig['autoload']['psr-4'] ?? [];

        return \array_combine(
            \array_map(
                static fn(string $namespacePrefix): string => \trim($namespacePrefix, '\\'),
                \array_keys($definedNamespaces),
            ),
            \array_map(
                static fn(string $rootFragment): string => \trim($rootFragment, '/'),
                \array_values($definedNamespaces),
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    private function excludedFiles(): array
    {
        $composerConfig = $this->getComposerConfiguration();

        return \array_map(
            fn(string $file): string => "{$this->kernelRootDir}/{$file}",
            $composerConfig['autoload']['files'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getComposerConfiguration(): array
    {
        $composerConfigFilePath = "{$this->kernelRootDir}/composer.json";
        if (!\is_file($composerConfigFilePath)) {
            return [];
        }

        return \json_decode((string) \file_get_contents($composerConfigFilePath), true, 512, \JSON_THROW_ON_ERROR);
    }
}
