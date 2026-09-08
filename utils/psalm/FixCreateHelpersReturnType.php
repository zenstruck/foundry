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

namespace Zenstruck\Foundry\Psalm;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralInt;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * The create helpers are declared with "@method static" tags on Factory, and Psalm does not
 * bind the class template when it resolves a pseudo method: it expands the return type with
 * the declaring class as "self", so "T" stays "T:Zenstruck\Foundry\Factory as mixed".
 *
 * The return type is therefore rebuilt here, once the call has been analyzed. This also
 * covers the proxy factories, which FixProxyFactoryMethodsReturnType cannot reach: it hooks
 * AfterMethodCallAnalysisInterface, and that never fires for a magic static call.
 */
final class FixCreateHelpersReturnType implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();

        if (!$expr instanceof StaticCall
            || !$expr->name instanceof Identifier
            || !$expr->class instanceof Name
        ) {
            return null;
        }

        $method = \strtolower($expr->name->name);

        if (!\in_array($method, ['createone', 'createmany', 'createrange', 'createsequence'], true)) {
            return null;
        }

        $source = $event->getStatementsSource();
        $class = ClassLikeAnalyzer::getFQCLNFromNameObject($expr->class, $source->getAliases());
        $codebase = $event->getCodebase();

        if (!$codebase->classExtends($class, Factory::class)) {
            return null;
        }

        $storage = $codebase->classlikes->getStorageFor($class);

        if (!$storage) {
            return null;
        }

        $isProxy = $codebase->classExtends($class, PersistentProxyObjectFactory::class);
        $templateType = $storage->template_extended_params[$isProxy ? PersistentProxyObjectFactory::class : Factory::class]['T'] ?? null;

        if (!$templateType) {
            return null;
        }

        $type = $templateType->getId();

        if ($isProxy) {
            $proxyClass = Proxy::class;
            $type = "{$type}&{$proxyClass}<{$type}>";
        }

        $source->getNodeTypeProvider()->setType(
            $expr,
            Type::parseString(self::returnType($method, $type, $expr, $source, $isProxy)),
        );

        return null;
    }

    /**
     * The list helpers are declared with a conditional return type, which Psalm does not
     * evaluate for a pseudo method either, so the argument is read here. Proxy factories
     * never had the conditional type, FixProxyFactoryMethodsReturnType always widened them
     * to a plain list, and that is kept.
     */
    private static function returnType(
        string $method,
        string $type,
        StaticCall $expr,
        StatementsSource $source,
        bool $isProxy,
    ): string {
        if ('createone' === $method) {
            return $type;
        }

        if ('createsequence' === $method || $isProxy) {
            return "list<{$type}>";
        }

        // createMany() is driven by its first argument, createRange() by its lowest bound
        $argument = $expr->getArgs()[0]->value ?? null;

        return $argument && self::isPositiveInt($argument, $source)
            ? "non-empty-list<{$type}>"
            : "list<{$type}>";
    }

    private static function isPositiveInt(Expr $expr, StatementsSource $source): bool
    {
        $type = $source->getNodeTypeProvider()->getType($expr);

        if (!$type || !$type->isSingle()) {
            return false;
        }

        $atomic = $type->getSingleAtomic();

        if ($atomic instanceof TLiteralInt) {
            return $atomic->value > 0;
        }

        return $atomic instanceof TIntRange && null !== $atomic->min_bound && $atomic->min_bound > 0;
    }
}
