<?php

function legacyProxy(\Zenstruck\Foundry\Proxy $proxy): void
{
    $proxy->object();
    $proxy?->object();
    $proxy->save();
    $proxy->remove();
    $proxy->refresh();
    $proxy->forceSet();
    $proxy->forceGet();
    $proxy->repository();
    $proxy->enableAutoRefresh();
    $proxy->disableAutoRefresh();
    $proxy->withoutAutoRefresh();
    $proxy->assertPersisted();
    $proxy->assertNotPersisted();
}

function newProxy(\Zenstruck\Foundry\Persistence\Proxy $proxy): void
{
    $proxy->object();
    $proxy?->object();
    $proxy->save();
    $proxy->remove();
    $proxy->refresh();
    $proxy->forceSet();
    $proxy->forceGet();
    $proxy->repository();
    $proxy->enableAutoRefresh();
    $proxy->disableAutoRefresh();
    $proxy->withoutAutoRefresh();
    $proxy->assertPersisted();
    $proxy->assertNotPersisted();
}

?>
-----
<?php

function legacyProxy(\Zenstruck\Foundry\Proxy $proxy): void
{
    $proxy->_real();
    $proxy?->_real();
    $proxy->_save();
    $proxy->_delete();
    $proxy->_refresh();
    $proxy->_set();
    $proxy->_get();
    $proxy->_repository();
    $proxy->_enableAutoRefresh();
    $proxy->_disableAutoRefresh();
    $proxy->_withoutAutoRefresh();
    $proxy->_assertPersisted();
    $proxy->_assertNotPersisted();
}

function newProxy(\Zenstruck\Foundry\Persistence\Proxy $proxy): void
{
    $proxy->_real();
    $proxy?->_real();
    $proxy->_save();
    $proxy->_delete();
    $proxy->_refresh();
    $proxy->_set();
    $proxy->_get();
    $proxy->_repository();
    $proxy->_enableAutoRefresh();
    $proxy->_disableAutoRefresh();
    $proxy->_withoutAutoRefresh();
    $proxy->_assertPersisted();
    $proxy->_assertNotPersisted();
}

?>
