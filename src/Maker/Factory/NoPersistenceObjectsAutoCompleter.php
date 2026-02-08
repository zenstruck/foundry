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
    /** @var array<string, mixed>|null */
    private ?array $composerConfig = null;

    public function __construct(private string $projectDir)
    {
    }

    /**
     * @return list<class-string>
     */
    public function getAutocompleteValues(): array
    {
        $excludedFiles = $this->excludedFiles();

        $classes = [];

        foreach ($this->psr4Namespaces() as $namespacePrefix => $rootFragment) {
            $rootPath = "{$this->projectDir}/{$rootFragment}";

            if (!\is_dir($rootPath)) {
                continue;
            }

            foreach ($this->phpFilesIn($rootPath) as $phpFile) {
                if (\in_array($phpFile->getRealPath(), $excludedFiles, true)) {
                    continue;
                }

                $class = $this->toClassName($rootPath, $phpFile, $namespacePrefix);

                try {
                    $reflection = new \ReflectionClass($class); // @phpstan-ignore argument.type
                } catch (\Throwable) {
                    continue;
                }

                if ($reflection->isInstantiable()) {
                    $classes[] = $reflection->getName();
                }
            }
        }

        \sort($classes);

        return $classes;
    }

    /**
     * @return \RegexIterator<int, \SplFileInfo, \RecursiveIteratorIterator<\RecursiveCallbackFilterIterator>>
     */
    private function phpFilesIn(string $directory): \RegexIterator
    {
        $iterator = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS);

        $filtered = new \RecursiveCallbackFilterIterator(
            $iterator,
            static fn(\SplFileInfo $file): bool => !$file->isDir() || !\str_contains($file->getPathname(), '/vendor/'),
        );

        return new \RegexIterator(new \RecursiveIteratorIterator($filtered), '/\.php$/'); // @phpstan-ignore return.type
    }

    private static function toClassName(string $rootPath, \SplFileInfo $fileInfo, string $namespacePrefix): string
    {
        $relativePath = \str_replace([$rootPath, '.php'], ['', ''], $fileInfo->getRealPath());

        return $namespacePrefix.\str_replace('/', '\\', $relativePath);
    }

    /**
     * @return array<string, string>
     */
    private function psr4Namespaces(): array
    {
        /** @var array<string, string> $namespaces */
        $namespaces = $this->composerConfig()['autoload']['psr-4'] ?? [];

        return \array_combine(
            \array_map(static fn(string $prefix): string => \trim($prefix, '\\'), \array_keys($namespaces)),
            \array_map(static fn(string $path): string => \trim($path, '/'), \array_values($namespaces)),
        );
    }

    /**
     * @return list<string>
     */
    private function excludedFiles(): array
    {
        return \array_values(\array_map(
            fn(string $file): string => "{$this->projectDir}/{$file}",
            $this->composerConfig()['autoload']['files'] ?? [],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function composerConfig(): array
    {
        if (null !== $this->composerConfig) {
            return $this->composerConfig;
        }

        $path = "{$this->projectDir}/composer.json";

        if (!\is_file($path)) {
            return $this->composerConfig = [];
        }

        return $this->composerConfig = \json_decode(\file_get_contents($path) ?: '{}', true, 512, \JSON_THROW_ON_ERROR);
    }
}
