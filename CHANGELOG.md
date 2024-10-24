# CHANGELOG

## [v2.2.0](https://github.com/zenstruck/foundry/releases/tag/v2.2.0)

October 24th, 2024 - [v2.1.0...v2.2.0](https://github.com/zenstruck/foundry/compare/v2.1.0...v2.2.0)

* a549c10 docs: using factories in data providers (#707) by @nikophil
* 470d927 docs: how to extend database reset mechanism (#706) by @nikophil
* 2014ed9 feature: allow to use `Factory::create()` and factory service in data providers (#648) by @nikophil
* df568da refactor: make "database reset" mechanism extendable (#690) by @nikophil
* 4fb0b25 docs: add missing docs (#703) by @nikophil
* fa1d527 minor: misc fixes for sca (#705) by @nikophil
* 0d570cc refactor: fix proxy system and introduce psalm extension (#704) by @nikophil

## [v2.1.0](https://github.com/zenstruck/foundry/releases/tag/v2.1.0)

October 3rd, 2024 - [v2.0.9...v2.1.0](https://github.com/zenstruck/foundry/compare/v2.0.9...v2.1.0)

* 0f72ea5 fix: allow non object state in stories (#699) by @Brewal
* 6482357 feat: allow to configure migrations configuration files (#686) by @MatTheCat

## [v2.0.9](https://github.com/zenstruck/foundry/releases/tag/v2.0.9)

September 2nd, 2024 - [v2.0.8...v2.0.9](https://github.com/zenstruck/foundry/compare/v2.0.8...v2.0.9)

* b0a5d3d Fix Psalm TooManyTemplateParams (#693) by @ddeboer

## [v2.0.8](https://github.com/zenstruck/foundry/releases/tag/v2.0.8)

August 29th, 2024 - [v2.0.7...v2.0.8](https://github.com/zenstruck/foundry/compare/v2.0.7...v2.0.8)

* 3eebbf9 Have `flush_after()` return the callback's return (#691) by @HypeMC
* 33d5870 doc: Fix range call instead of many (#688) by @ternel
* 33595b9 chore: add a wrapper for PHPUnit binary (#683) by @nikophil
* 8bf8c4c docs: Fix CategoryStory codeblock (#681) by @smnandre
* f89d43e doc: Minor fixes (#679) by @smnandre
* 65c1cc2 fix: add phpdoc to improve proxy factories autocompletion (#675) by @nikophil

## [v2.0.7](https://github.com/zenstruck/foundry/releases/tag/v2.0.7)

July 12th, 2024 - [v2.0.6...v2.0.7](https://github.com/zenstruck/foundry/compare/v2.0.6...v2.0.7)

* 5c44991 fix: handle proxies when refreshing entity in Proxy::getState() (#672) by @nikophil
* 49f5e1d Fix faker php urls (#671) by @BackEndTea
* 7719b0d chore(CI): Enable documentation linter (#657) by @cezarpopa

## [v2.0.6](https://github.com/zenstruck/foundry/releases/tag/v2.0.6)

July 4th, 2024 - [v2.0.5...v2.0.6](https://github.com/zenstruck/foundry/compare/v2.0.5...v2.0.6)

* 52ca7b7 fix: only restore error handler for PHPUnit 10 or superior (#668) by @nikophil
* b5090aa docs: Fix broken link to Without Persisting (#660) by @simoheinonen
* 35b0404 feat: re-add Proxy assertions (#663) by @nikophil

## [v2.0.5](https://github.com/zenstruck/foundry/releases/tag/v2.0.5)

July 3rd, 2024 - [v2.0.4...v2.0.5](https://github.com/zenstruck/foundry/compare/v2.0.4...v2.0.5)

* 6105a36 fix: make proxy work with last symfony/var-exporter version (#664) by @nikophil
* e8623a3 [DOC] Fix Upgrade Guide URL Rendering (#654) by @cezarpopa
* f7f133a fix: create ArrayCollection if needed (#645) by @nikophil
* 779bee4 fix: after_flush() can use objects created in global state (#653) by @nikophil
* 72e48bf tests(ci): add test permutation for PHPUnit >= 10 (#647) by @nikophil
* 1edf948 docs: fix incoherence (#652) by @nikophil
* 1c66e39 minor: improve repository assertion messages (#651) by @nikophil

## [v2.0.4](https://github.com/zenstruck/foundry/releases/tag/v2.0.4)

June 20th, 2024 - [v2.0.3...v2.0.4](https://github.com/zenstruck/foundry/compare/v2.0.3...v2.0.4)

* 0989c5d fix: don't try to proxify objects that are not persistable (#646) by @nikophil
* 50ae3dc fix: handle contravariance problem when proxifying class with unserialize method (#644) by @nikophil

## [v2.0.3](https://github.com/zenstruck/foundry/releases/tag/v2.0.3)

June 19th, 2024 - [v2.0.2...v2.0.3](https://github.com/zenstruck/foundry/compare/v2.0.2...v2.0.3)

* 6f0835f fix(2.x): only reset error handler in before class hook (#643) by @nikophil
* 3c31193 test: add test with multiple ORM schemas (#629) by @vincentchalamon
* 303211a fix: unproxy args in proxy objects (#635) by @nikophil

## [v2.0.2](https://github.com/zenstruck/foundry/releases/tag/v2.0.2)

June 14th, 2024 - [v2.0.1...v2.0.2](https://github.com/zenstruck/foundry/compare/v2.0.1...v2.0.2)

* b76c294 fix(2.x): support Symfony 7.1 (#622) by @nikophil
* 9cd97b7 docs: Improve DX for tests (#636) by @matthieumota
* 17b0228 fix(2.x): add back second parameter for after persist callbacks (#631) by @nikophil
* 0c7b3af docs: Fix typo in the upgrade guide (#624) by @stof
* 933ebbd docs: upgrade readme with a link to upgrade guide (#620) by @nikophil

## [v2.0.1](https://github.com/zenstruck/foundry/releases/tag/v2.0.1)

June 10th, 2024 - [v2.0.0...v2.0.1](https://github.com/zenstruck/foundry/compare/v2.0.0...v2.0.1)

* 5f0ce76 Fix `Instantiator::allowExtra` example (#616) by @norkunas
* c2cbcbc fix(orm): reset database instead of dropping the schema when using migrations (#615) by @vincentchalamon

## [v2.0.0](https://github.com/zenstruck/foundry/releases/tag/v2.0.0)

June 7th, 2024 - _[Initial Release](https://github.com/zenstruck/foundry/commits/v2.0.0)_
