<?php

function legacyProxy(\Zenstruck\Foundry\Proxy $proxy): void
{
    $proxy->object();
    $proxy?->object();
    $proxy->save();
    $proxy->remove();
    $proxy->refresh();
    $proxy->forceSet();
    $proxy->get();
    $proxy->repository();
    $proxy->enableAutoRefresh();
    $proxy->disableAutoRefresh();
    $proxy->withoutAutoRefresh();
}

function newProxy(\Zenstruck\Foundry\Persistence\Proxy $proxy): void
{
    $proxy->object();
    $proxy?->object();
    $proxy->save();
    $proxy->remove();
    $proxy->refresh();
    $proxy->forceSet();
    $proxy->get();
    $proxy->repository();
    $proxy->enableAutoRefresh();
    $proxy->disableAutoRefresh();
    $proxy->withoutAutoRefresh();
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
}

?>
