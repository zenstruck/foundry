<?php

namespace Zenstruck\Foundry\Proxy;

use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Configuration;

/**
 * Generates model proxies to autorefresh property values
 * from the database.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class ProxyGenerator
{
    /** @var Configuration */
    private $configuration;
    /** @var ValueReplacingAccessInterceptorValueHolderFactory */
    private $factory;

    public function __construct(Configuration $configuration, ?ValueReplacingAccessInterceptorValueHolderFactory $factory = null)
    {
        $this->configuration = $configuration;
        $this->factory = $factory ?? new ValueReplacingAccessInterceptorValueHolderFactory();
    }

    public function generate(object $object, array $methods = []): object
    {
        $objectManager = $this->configuration->objectManagerFor(\get_class($object));
        $interceptor = function(object $proxy, object $model) use ($objectManager): void {
            $autoRefreshEnabled = \property_exists($model, '_foundry_autoRefresh') ? $model->_foundry_autoRefresh : true;
            if (!$autoRefreshEnabled) {
                return;
            }

            $modelClassMetadata = $objectManager->getClassMetadata(\get_class($model));

            // only check for changes if the object is managed in the current om
            if ($objectManager instanceof EntityManagerInterface && $objectManager->contains($model)) {
                // cannot use UOW::recomputeSingleEntityChangeSet() here as it wrongly computes embedded objects as changed
                $objectManager->getUnitOfWork()->computeChangeSet($modelClassMetadata, $model);

                if (!empty($objectManager->getUnitOfWork()->getEntityChangeSet($model))) {
                    throw new \RuntimeException(\sprintf('Cannot auto refresh "%s" as there are unsaved changes. Be sure to call ->save() or disable auto refreshing (see https://github.com/zenstruck/foundry#auto-refresh for details).', \get_class($model)));
                }

                $objectManager->refresh($model);

                return;
            }

            // refetch the model as it's no longer managed
            $modelId = $modelClassMetadata->getIdentifierValues($model);
            if (!$modelId) {
                throw new \RuntimeException('The object no longer exists.');
            }

            $proxy->setWrappedValueHolder($objectManager->find(\get_class($model), $modelId));
        };

        $methodsToIntercept = $this->filterGlob(\get_class_methods($object), $methods);

        return $this->factory->createProxy($object, \array_fill_keys($methodsToIntercept, $interceptor));
    }

    private function filterGlob(array $objectMethods, array $methodFilter): array
    {
        $methods = [];
        if (!$methodFilter) {
            $methods = $objectMethods;
        } else {
            foreach ($methodFilter as $filter) {
                // no *, assume filter is an exact match and no glob
                if (false === \mb_strpos($filter, '*')) {
                    $methods[] = $filter;
                    continue;
                }

                // transform glob (e.g. "get*") into a regex
                $methodNameRegex = '/^'.\str_replace('*', '[[:alnum:]]+', $filter).'$/';
                $methods = \array_merge($methods, \array_filter($objectMethods, function($objectMethod) use ($methodNameRegex) {
                    return 1 !== \preg_match($methodNameRegex, $objectMethod);
                }));
            }
        }

        return \array_unique($methods);
    }
}
