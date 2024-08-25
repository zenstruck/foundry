<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\Persistence\ResetDatabase\SchemaResetterInterface;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DamaSchemaResetter implements SchemaResetterInterface
{
    public function __construct(
        private SchemaResetterInterface $decorated,
    ) {
    }

    public function resetSchema(KernelInterface $kernel): void
    {
        if (ResetDatabaseManager::isDAMADoctrineTestBundleEnabled()) {
            // not required as the DAMADoctrineTestBundle wraps each test in a transaction
            return;
        }

        $this->decorated->resetSchema($kernel);
    }
}
