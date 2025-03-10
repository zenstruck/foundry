# CHANGELOG

## [v2.3.6](https://github.com/zenstruck/foundry/releases/tag/v2.3.6)

February 25th, 2025 - [v2.3.5...v2.3.6](https://github.com/zenstruck/foundry/compare/v2.3.5...v2.3.6)

* 300645b fix: can call ->create() in after persist callback (#833) by @nikophil

## [v2.3.5](https://github.com/zenstruck/foundry/releases/tag/v2.3.5)

February 24th, 2025 - [v2.3.4...v2.3.5](https://github.com/zenstruck/foundry/compare/v2.3.4...v2.3.5)

* fbf0981 fix: actually disable persistence cascade (#817) by @nikophil
* 2426f3e fix: trigger after persist callbacks for entities scheduled for insert (#822) by @nikophil

## [v2.3.4](https://github.com/zenstruck/foundry/releases/tag/v2.3.4)

February 14th, 2025 - [v2.3.3...v2.3.4](https://github.com/zenstruck/foundry/compare/v2.3.3...v2.3.4)

* ad8d72c fix: can index one to many relationships based on "indexBy" (#815) by @nikophil

## [v2.3.2](https://github.com/zenstruck/foundry/releases/tag/v2.3.2)

February 1st, 2025 - [v2.3.1...v2.3.2](https://github.com/zenstruck/foundry/compare/v2.3.1...v2.3.2)

* 46464cc chore(ci): misc improvments in CI permutations (#797) by @nikophil
* 86c5aab test: assert updates are implicitly persisted (#781) by @nikophil
* 54c7424 feat: deprecate when Factories trait is not used in a KernelTestCase (#766) by @nikophil
* 9937b11 chore: add issue template (#795) by @nikophil
* 884113f fix: simplify reset database extension (#779) by @nikophil
* bd50f41 fix: add unpersisted object to relation (#780) by @nikophil
* 17388bc tests: transform "migrate" testsuite into "reset database" testsuite (#763) by @nikophil
* e45913e fix: propagate "schedule for insert" to factory collection (#775) by @nikophil
* d9262cc fix: fix .gitattributes and `#[RequiresPhpUnit]` versions (#792) by @nikophil
* 57c42bc tests: fix a test after a bug was resolved in doctrine migrations (#791) by @nikophil
* 200cfdd [Doc] Fix misc issues (#789) by @javiereguiluz
* 553807b minor: add platform config to mysql docker container (#788) by @kbond
* 316d3c7 doc: fix typo (#782) by @norival
* 0d66c02 minor: use refresh for detached entities (#778) by @nikophil
* 29b48a1 test: add orphan removal premutation (#777) by @nikophil
* c00b3f1 fix: isPersisted must work when id is known in advance (#774) by @nikophil
* f303f3f fix: remove _refresh call from create object process (#773) by @nikophil
* 65cedbf fix: use a "placeholder" for inversed one-to-one (#755) by @nikophil
* 5f99506 minor: introduce PerssitenceManager::isPersisted() (#754) by @nikophil
* 9948d6a fix(ci): change PHP version used by PHP CS-Fixer  (#768) by @nikophil
* cf3cc8b docs: Minor syntax fix (#767) by @javiereguiluz
* e8f9a92 docs: clarify default attributes and fixed some syntax issues (#765) by @nikophil, @javiereguiluz
* 1db5ced tests: validate PSR-4 in CI (#762) by @nikophil
* cafc693 [Docs fix] Just spelling in docs (#761) by @GrinWay
* d192c4a [Docs fix] Proxy::_save() instead of Proxy::save() (#760) by @GrinWay
* ff7210a [Docs fix] Factory::_real() instead Factory::object() (#759) by @GrinWay
* d1240b1 fix: RequiresPhpunit should use semver constraint by @nikophil
* fd2e38c chore: upgrade to phpstan 2 (#748) by @nikophil
* 23b4ec4 tests: automatically create cascade persist permutations (#666) by @nikophil
* f4ba5d8 tests: add CI permutation with windows (#747) by @nikophil
* c17ef91 fix: define FactoryCollection type more precisely (#744) by @nikophil
* 98f018c feat: schedule objects for insert right after instantiation (#742) by @nikophil
* 2dcad10 feat: provide current factory to hook (#738) by @nikophil
* ea89504 fix: pass to `afterPersist` hook the attributes from `beforeInstantiate` (#745) by @nikophil, @kbond

## [v2.3.1](https://github.com/zenstruck/foundry/releases/tag/v2.3.1)

December 12th, 2024 - [v2.3.0...v2.3.1](https://github.com/zenstruck/foundry/compare/v2.3.0...v2.3.1)

* 138801d chore: remove error handler hack (#729) by @nikophil
* cd9dbf5 refactor: extract reset:migration tests in another testsuite (#692) by @nikophil

## [v2.3.0](https://github.com/zenstruck/foundry/releases/tag/v2.3.0)

December 11th, 2024 - [v2.2.2...v2.3.0](https://github.com/zenstruck/foundry/compare/v2.2.2...v2.3.0)

* b16b227 Update index.rst (#740) by @OskarStark, @nikophil
* 854220f Figo highlighting and use CPP (#740) by @OskarStark
* dfe6bab tests: add paratest permutation (#736) by @nikophil
* af64c35 fix: detect if relation is oneToOne (#732) by @nikophil
* 59867c3 minor: change versions requirements (#737) by @nikophil
* c8f5046 Fix PHPUnit constraint requirement in FoundryExtension (#735) by @HypeMC
* 4cb7447 Typo in Immutable section (#731) by @franckranaivo
* 403d9e9 fix: Fix the parameter name of the first and last methods (#730) by @marien-probesys
* 0867ad6 feat: add `#[WithStory]` attribute (#728) by @nikophil
* c5d0bdd fix: can create inversed one to one with non nullable (#726) by @nikophil
* 0e7ac6f docs: Fix Story phpdocs (#727) by @simondaigre, @nikophil
* f48ffd1 fix: can create inversed one to one (#659) by @nikophil
* 6d08784 fix: bug with one to many (#722) by @nikophil
* efadea8 docs:fix code blocks not showing up (#723) by @AndreasA
* edf287e minor: Add templated types to flush_after (#719) by @BackEndTea

## [v2.2.2](https://github.com/zenstruck/foundry/releases/tag/v2.2.2)

November 5th, 2024 - [v2.2.1...v2.2.2](https://github.com/zenstruck/foundry/compare/v2.2.1...v2.2.2)

* 3282f24 Remove @internal from db resetter interfaces (#715) by @HypeMC
* 870cb42 docs: fix missing comma in upgrade doc (#718) by @justpilot

## [v2.2.1](https://github.com/zenstruck/foundry/releases/tag/v2.2.1)

October 31st, 2024 - [v2.2.0...v2.2.1](https://github.com/zenstruck/foundry/compare/v2.2.0...v2.2.1)

* 496a7a8 fix: Change `RepositoryDecorator::inner()` visibility to public (#714) by @marienfressinaud
* dfeb247 chore: test Foundry on PHP 8.4 & sf 7.2 (#709) by @nikophil
* 2b12ef0 chore: simplify CI matrix (#708) by @nikophil

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
