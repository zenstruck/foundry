<?php

use Zenstruck\Foundry\Persistence\RepositoryDecorator;
use Zenstruck\Foundry\RepositoryProxy;

class_alias(\Zenstruck\Foundry\Persistence\RepositoryAssertions::class, \Zenstruck\Foundry\RepositoryAssertions::class);
class_alias(RepositoryDecorator::class, RepositoryProxy::class);
class_alias(\Zenstruck\Foundry\Persistence\Proxy::class, \Zenstruck\Foundry\Proxy::class);
