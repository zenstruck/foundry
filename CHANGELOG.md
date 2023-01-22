# CHANGELOG

## [v1.27.0](https://github.com/zenstruck/foundry/releases/tag/v1.27.0)

January 9th, 2023 - [v1.26.0...v1.27.0](https://github.com/zenstruck/foundry/compare/v1.26.0...v1.27.0)

* 7b97ac2 feat: add $criteria param to RepositoryAssertions::empty() (#391) by @nikophil

## [v1.26.0](https://github.com/zenstruck/foundry/releases/tag/v1.26.0)

December 29th, 2022 - [v1.25.0...v1.26.0](https://github.com/zenstruck/foundry/compare/v1.25.0...v1.26.0)

* 79913c3 feat: create  parameter to RepositoryAssertions::count() methods (#390) by @nikophil
* 4df0f40 chore: improve makefile (#382) by @nikophil
* 49da6a0 feat(make:factory): use autocompletion for no persistence classes (#383) by @nikophil

## [v1.25.0](https://github.com/zenstruck/foundry/releases/tag/v1.25.0)

December 22nd, 2022 - [v1.24.1...v1.25.0](https://github.com/zenstruck/foundry/compare/v1.24.1...v1.25.0)

* b101604 fix: ci by @kbond
* cc22eac chore: fix cs (#386) by @kbond
* e0944bb chore(ci): sync meta files and automate cs fixer (#386) by @kbond
* d970d7a minor: Reference non deprecated method (#387) by @jongotlin
* 7ab0740 minor(story): make `Story::getState()` protected (#385) by @kbond
* 9a6f28e chore: availability to chose php version (#376) (#381) by @nikophil
* aaeb6cf doc: display downloads badge (#380) by @kbond
* e4d5fcb chore(ci): test on PHP 8.2 (#361) by @kbond
* 555d547 chore: change test context with .env (#375) by @nikophil
* 2eb52ed feat(make:factory): auto create missing factories (#372) by @nikophil
* 6bc81b1 refactor: set all fixtures class name unique (#374) by @nikophil
* 892ed14 feat(make:factory): improve Doctrine default fields guesser (#364) by @nikophil
* 7b08360 doc: fix header (#370) by @seb-jean

## [v1.24.1](https://github.com/zenstruck/foundry/releases/tag/v1.24.1)

November 29th, 2022 - [v1.24.0...v1.24.1](https://github.com/zenstruck/foundry/compare/v1.24.0...v1.24.1)

* 6588804 dependencies: allow symfony/string 5.4 (#369) by @HypeMC
* 9e5450e docs: fix namespaces in global_state example (#366) by @OskarStark
* 917aba5 docs: fix onfig value (#368) by @OskarStark
* 7773f7e docs: fix config key (#367) by @OskarStark

## [v1.24.0](https://github.com/zenstruck/foundry/releases/tag/v1.24.0)

November 25th, 2022 - [v1.23.0...v1.24.0](https://github.com/zenstruck/foundry/compare/v1.23.0...v1.24.0)

* f5e9eae minor: use --no-persistence instead of --not-persisted (#365) by @nikophil
* 730c0d9 chore: rename service ids (#363) by @kbond
* 19acc72 feat: add `RepositoryProxy::inner()` (#362) by @kbond
* a003bac refactor(make:factory): split command with DefaultPropertiesGuesser (#357) by @nikophil
* d8eca88 chore(ci): test on Symfony 6.2 (#359) by @kbond
* 20ac349 refactor(make:factory): use value object to render template (#354) by @nikophil
* 4e5f9d9 feat(make:factory): use factories to default non-nullable relationships (#351) by @nikophil, @benblub
* 8332956 feat: make `Story::get()` static (implies `Story::load()->get()`) (#253) by @kbond
* b89bcff minor(make:factory): misc enhancements of maker (#345) by @nikophil
* 3cc95a5 minor: remove php 7.4 related tests (#349) by @nikophil
* 8a055b0 chore: fix docker cache (#350) by @nikophil
* 18ea4fb feat(make:factory): create factory for not-persisted objects (#343) by @nikophil
* 64786fc fix: typo in docs (#348) by @nikophil
* 1a98fc4 chore: Use composer 2.4 (#346) by @OskarStark
* 96c4cbe minor(make:factory): Use `@see`/`@todo` annotations (#344) by @OskarStark
* cbeb2ce fix: adjust docblocks to remove PhpStorm errors (#341) by @kbond
* cd1e394 fix: use orm limit length in factory (#294) by @MrYamous
* b1d7ce3 [feature] add default for Mongo properties in (#340) by @nikophil
* 778607a [chore] use cache for docker CI (#339) by @nikophil
* c662eb3 [feature] auto add phpstan annotations in make:factory (#338) by @nikophil
* 2bd046f [docs] sort phpstan-method annotations (#333) by @OskarStark
* 0460741 [docs] remove obsolete section (#335) by @nikophil
* 2c34baf [bug] Typos in Makefile (#330) by @OskarStark
* 65924b2 [bug] typos in docs (#331) by @OskarStark
* 6b48878 [chore] upgrade ci actions (#329) by @kbond
* 210faff [chore] use phpstan instead of psalm (#328) by @nikophil
* 51f1bc0 [refactor] modernize code with rector (#327) by @nikophil
* 8423b75 [chore] adjust `.symfony.bundle.yaml` for new branch (#325) by @kbond
* 5a05513 [feature] require php8+ (#327) by @kbond

## [v1.23.0](https://github.com/zenstruck/foundry/releases/tag/v1.23.0)

November 10th, 2022 - [v1.22.1...v1.23.0](https://github.com/zenstruck/foundry/compare/v1.22.1...v1.23.0)

* f43b067 [chore] clean up CI (#324) by @nikophil
* 3588274 [feature] Allow to use foundry without Doctrine (#323) by @nikophil
* 7598467 [feature] [remove bundleless usge] configure global state with config (#322) by @nikophil
* e417945 [feature] [remove bundleless usge] use config instead of environment variables (#320) by @nikophil
* cada0cf [feature] pass an index to `FactoryCollection` attributes (#318) by @nikophil
* d120b1c [minor] fix `bamarni/composer-bin-plugin` deprecations (#313) by @kbond
* a3eefc1 [minor] remove branch alias (#313) by @kbond
* cf7d75e [minor] remove unneeded bin script (#310) by @kbond
* cd42774 [feature] add make migrations (#309) by @nikophil
* cb9a4ec [feature] add a docker stack (#306) by @nikophil

## [v1.22.1](https://github.com/zenstruck/foundry/releases/tag/v1.22.1)

September 28th, 2022 - [v1.22.0...v1.22.1](https://github.com/zenstruck/foundry/compare/v1.22.0...v1.22.1)

* 8d41ca8 [bug] discover relations with inheritance (#300) by @NorthBlue333
* ae6bda2 [bug] multiple relationships with same entity (#302) by @NorthBlue333

## [v1.22.0](https://github.com/zenstruck/foundry/releases/tag/v1.22.0)

September 21st, 2022 - [v1.21.1...v1.22.0](https://github.com/zenstruck/foundry/compare/v1.21.1...v1.22.0)

* 4fb5fb8 [feature] Introduce Sequences (#298) by @nikophil

## [v1.21.1](https://github.com/zenstruck/foundry/releases/tag/v1.21.1)

September 12th, 2022 - [v1.21.0...v1.21.1](https://github.com/zenstruck/foundry/compare/v1.21.0...v1.21.1)

* 3b105a7 [bug] Fix usage of faker dateTime in factory maker (#297) by @jmsche
* 0663f29 [doc] Fix code block docs about faker seed (#296) by @jmsche
* b57d067 [doc] fix typo (#295) by @Chris53897
* 4577ef4 [minor] Improve deprecation message for `createMany()` (#291) by @gazzatav, @kbond

## [v1.21.0](https://github.com/zenstruck/foundry/releases/tag/v1.21.0)

June 27th, 2022 - [v1.20.0...v1.21.0](https://github.com/zenstruck/foundry/compare/v1.20.0...v1.21.0)

* e02fbe1 [doc] update config for 5.4+ (#285) by @kbond
* 39258de [feature] add configuration option for faker generator seed (#285) by @kbond
* 1bd05ce [feature] re-save created object after "afterPersist" events called (#279) by @kbond
* 195c815 [bug] Use DateTimeImmutable with immutable ORM types (#283) by @HypeMC

## [v1.20.0](https://github.com/zenstruck/foundry/releases/tag/v1.20.0)

June 20th, 2022 - [v1.19.0...v1.20.0](https://github.com/zenstruck/foundry/compare/v1.19.0...v1.20.0)

* 6009499 [feature] add `Story::getPool()` (#282) by @kbond

## [v1.19.0](https://github.com/zenstruck/foundry/releases/tag/v1.19.0)

May 24th, 2022 - [v1.18.2...v1.19.0](https://github.com/zenstruck/foundry/compare/v1.18.2...v1.19.0)

* 46de01a [feature] Handle variadic constructor arguments (#277) by @ndench
* f5d9177 [minor] use symfony/phpunit-bridge 6+ by @kbond
* 09b0ae2 [minor] fix sca by @kbond

## [v1.18.2](https://github.com/zenstruck/foundry/releases/tag/v1.18.2)

April 29th, 2022 - [v1.18.1...v1.18.2](https://github.com/zenstruck/foundry/compare/v1.18.1...v1.18.2)

* 2b2d2e7 [minor] allow `doctrine/persistence` 3 (#275) by @kbond
* 429466e [doc] add note about phpstan docblocks (#274) by @kbond, Jacob Dreesen <jacob@hdreesen.de>

## [v1.18.1](https://github.com/zenstruck/foundry/releases/tag/v1.18.1)

April 22nd, 2022 - [v1.18.0...v1.18.1](https://github.com/zenstruck/foundry/compare/v1.18.0...v1.18.1)

* ff9e4ef [bug] fix embeddable support when used with file (ie xml) mapping (#271) by @kbond
* 40a5a1e [minor] support Symfony 6.1 (#267) by @kbond

## [v1.18.0](https://github.com/zenstruck/foundry/releases/tag/v1.18.0)

April 11th, 2022 - [v1.17.0...v1.18.0](https://github.com/zenstruck/foundry/compare/v1.17.0...v1.18.0)

* b9d2ed3 [feature] add `Factory::delayFlush()` (#84) by @kbond
* 91609b4 [minor] remove scrutinizer (#266) by @kbond
* 8117f40 [minor] allow `dama/doctrine-test-bundle` 7.0 (#266) by @kbond
* 6052e81 [minor] Revert "[bug] fix global state with symfony/framework-bundle >= 5.4.6/6.0.6" (#260) by @kbond

## [v1.17.0](https://github.com/zenstruck/foundry/releases/tag/v1.17.0)

March 24th, 2022 - [v1.16.0...v1.17.0](https://github.com/zenstruck/foundry/compare/v1.16.0...v1.17.0)

* c131715 [bug] fix global state with symfony/framework-bundle >= 5.4.6/6.0.6 (#259) by @kbond
* 0edbea8 [minor] remove Symfony 5.3 from test matrix (#259) by @kbond
* 5768345 [feature] add Story "pools" (#252) by @kbond
* be6b6c8 Revert "[feature] Allow any type for Story States (#231)" (#252) by @kbond
* 02cd0c8 [minor] deprecate `Story:add()` and add `Story::addState()` (#254) by @kbond
* 02609a9 [minor] add return type for stub command (deprecated in symfony 6) (#257) by @Chris53897, Christopher Georg <christopher.georg@sr-travel.de>
* 6977f3a [doc] Use `UserPasswordHasherInterface` instead of `UserPasswordEncoderInterface` (#255) by @zairigimad
* 01ebfab [feature] add an 'All' option to make:factory to create all missing factories (#247) by @abeal-hottomali
* 39fa8e2 [bug] ignore abstract classes in the maker (#249) by @abeal-hottomali
* 62eeb75 [minor] run php-cs-fixer on php 7.2 (#243) by @kbond

## [v1.16.0](https://github.com/zenstruck/foundry/releases/tag/v1.16.0)

January 6th, 2022 - [v1.15.0...v1.16.0](https://github.com/zenstruck/foundry/compare/v1.15.0...v1.16.0)

* 79261d6 [feature] MongoDB ODM Support (#153) by @kbond, @nikophil
* d97d895 [minor] fix psalm (#232) by @kbond
* fc74f26 [minor] add allow-plugins for composer 2.2+ (#232) by @kbond

## [v1.15.0](https://github.com/zenstruck/foundry/releases/tag/v1.15.0)

December 30th, 2021 - [v1.14.1...v1.15.0](https://github.com/zenstruck/foundry/compare/v1.14.1...v1.15.0)

* fb79022 [feature] Allow any type for Story States (#231) by @wouterj
* d6d7d52 [doc] update url (#230) by @bfoks
* 4915b61 [doc] Fix event hook argument name (#229) by @Aeet, @kbond
* 7e13ed0 [doc] add note about how attributes are built (#228) by @gnito-org
* 552dc6f [doc] Correct spelling in index.rst (#226) by @gnito-org
* 50a91b9 [bug] Fix smallint generated Faker (#223) by @jmsche
* 68552a7 [doc] Document the MakeFactory all-fields option (#220) by @gnito-org
* 93a2f9c [feature] Add all-fields option to MakeFactory (#218) by @gnito-org

## [v1.14.1](https://github.com/zenstruck/foundry/releases/tag/v1.14.1)

December 2nd, 2021 - [v1.14.0...v1.14.1](https://github.com/zenstruck/foundry/compare/v1.14.0...v1.14.1)

* bf1cbc9 [minor] Bump symfony/http-kernel from 5.3.7 to 5.4.0 in /bin/tools/psalm (#217) by @dependabot[bot], dependabot[bot] <49699333+dependabot[bot]@users.noreply.github.com>
* 433f9b2 [minor] allow symfony/deprecation-contracts 3+ by @kbond
* 2b10729 [minor] fix failing test by @kbond
* b3ce03f [minor] add void return type (#176) by @seb-jean

## [v1.14.0](https://github.com/zenstruck/foundry/releases/tag/v1.14.0)

November 13th, 2021 - [v1.13.4...v1.14.0](https://github.com/zenstruck/foundry/compare/v1.13.4...v1.14.0)

* 46e968e [minor] add Symfony 6.0/PHP 8.1 support (#198) by @kbond

## [v1.13.4](https://github.com/zenstruck/foundry/releases/tag/v1.13.4)

October 21st, 2021 - [v1.13.3...v1.13.4](https://github.com/zenstruck/foundry/compare/v1.13.3...v1.13.4)

* 676f00a [bug] disable migration transactions (#207) by @kbond
* c6df43d [ci] re-enable migration tests on php >= 8 (#207) by @kbond
* 2ef6c6a [ci] disable migration tests on php >= 8 (#206) by @kbond
* e29a008 [bug] fix maker auto-defaults with yaml driver (#205) by @domagoj-orioly
* c483b2e [ci] use reusable workflows where possible (#203) by @kbond
* 886204f [minor] adjust CI output by @kbond
* 63a956e [minor] use zenstruck/assert for assertions instead of phpunit (#182) by @kbond

## [v1.13.3](https://github.com/zenstruck/foundry/releases/tag/v1.13.3)

September 24th, 2021 - [v1.13.2...v1.13.3](https://github.com/zenstruck/foundry/compare/v1.13.2...v1.13.3)

* 477db0a [minor] install psalm as composer-bin tool (#199) by @kbond
* 6ced887 [minor] add Symfony 5.4 to test matrix (#197) by @kbond
* 6610c5d [bug] rename "rank" as it's a reserved keyword in mysql 8 (#197) by @kbond

## [v1.13.2](https://github.com/zenstruck/foundry/releases/tag/v1.13.2)

September 3rd, 2021 - [v1.13.1...v1.13.2](https://github.com/zenstruck/foundry/compare/v1.13.1...v1.13.2)

* 06b24d4 [bug] when creating collections, check for is persisting first (#195) by @jordisala1991, @kbond

## [v1.13.1](https://github.com/zenstruck/foundry/releases/tag/v1.13.1)

August 31st, 2021 - [v1.13.0...v1.13.1](https://github.com/zenstruck/foundry/compare/v1.13.0...v1.13.1)

* 1dccda1 [bug] fix/improve embeddable support (#193) by @kbond
* 5f39d8a [minor] update symfony-tools/docs-builder by @kbond

## [v1.13.0](https://github.com/zenstruck/foundry/releases/tag/v1.13.0)

August 30th, 2021 - [v1.12.0...v1.13.0](https://github.com/zenstruck/foundry/compare/v1.12.0...v1.13.0)

* 39f69f9 [doc] update symfony.com links (#191) by @kbond
* 31b7569 [doc] switch documentation to symfony.com bundle doc format (#190) by @kbond, @wouterj
* d4943d7 [minor] exclude maker templates from phpunit code coverage (#189) by @kbond
* 60f78b3 [doc] add note about simplified factory annotations in PhpStorm 2021.2+ (#189) by @kbond
* 8769d7a [minor] update factory maker template annotations (#189) by @kbond
* 1faf97d [feature] persisting factories respect cascade persist (#181) by @mpiot
* 0416dc4 [minor] Be able to remove most of method annotations on user factories (#185) by @Nyholm
* 468e80b [minor] add missing ->expectDeprecation() to legacy tests (#188) by @kbond
* 1647e1b [bug] ensure legacy test works as expected (#187) by @kbond
* a6f6413 [minor] Added .editorconfig to sync up styles (#186) by @Nyholm
* 2517f54 [minor] change Instantiator::$forceProperties type-hint (#183) by @kbond
* b145059 [minor] psalm fix (#180) by @kbond

## [v1.12.0](https://github.com/zenstruck/foundry/releases/tag/v1.12.0)

July 6th, 2021 - [v1.11.1...v1.12.0](https://github.com/zenstruck/foundry/compare/v1.11.1...v1.12.0)

* 6b97f0f [minor] refactor make:factory auto-default feature (#174) by @kbond
* 2a0bbce [feature] Auto populate ModelFactory::getDefaults() from doctrine mapping (#173) by @benblub

## [v1.11.1](https://github.com/zenstruck/foundry/releases/tag/v1.11.1)

June 25th, 2021 - [v1.11.0...v1.11.1](https://github.com/zenstruck/foundry/compare/v1.11.0...v1.11.1)

* ccac05c [minor] disable codecov pr annotations (#172) by @kbond
* 5c3abe2 [bug] allow passing full namespace with make:factory (#171) by @kbond

## [v1.11.0](https://github.com/zenstruck/foundry/releases/tag/v1.11.0)

June 4th, 2021 - [v1.10.0...v1.11.0](https://github.com/zenstruck/foundry/compare/v1.10.0...v1.11.0)

* d2cd4c7 [feature] customize namespace for factories generated with make:factory (#164) by @kbond
* b9c161b [minor] suppress psalm error (#165) by @kbond
* 112d57d [feature] make:factory only lists entities w/o factories (#162) by @jschaedl
* 6001e7e [minor] Detect missing maker bundle and suggest installation via StubCommands (#161) by @jschaedl
* cdab96e [minor] upgrade to php-cs-fixer 3 (#159) by @kbond
* d357215 [minor] update php-cs-fixer config (#159) by @kbond
* 3ce7da0 [minor] Update .gitattributes file (#158) by @ker0x
* be1d899 [doc] Fix example $posts for Attributes section (#155) by @babeuloula

## [v1.10.0](https://github.com/zenstruck/foundry/releases/tag/v1.10.0)

April 19th, 2021 - [v1.9.1...v1.10.0](https://github.com/zenstruck/foundry/compare/v1.9.1...v1.10.0)

* 7dc49f0 [minor] unlock php-cs-fixer in gh action by @kbond
* e800c83 [feature] add option to use doctrine migrations to reset database (#145) by @kbond
* 8466067 [doc] fix small typo in docs (#147) by @nikophil
* 69fe2a6 [doc] fix typo (#146) by @AntoineRoue
* 463d32a [minor] fix faker deprecations by @kbond
* aa6b32a [minor] lock php-cs-fixer version in ci (bug in latest release) by @kbond
* 4dc13e6 [minor] adjust codecov threshold by @kbond
* cd5cefe [minor] use SHELL_VERBOSITY to hide logs during tests by @kbond

## [v1.9.1](https://github.com/zenstruck/foundry/releases/tag/v1.9.1)

March 19th, 2021 - [v1.9.0...v1.9.1](https://github.com/zenstruck/foundry/compare/v1.9.0...v1.9.1)

* 0ebf0dd [bug] fix false positive for auto-refresh deprecation (fixes #141) (#143) by @kbond
* 7e693ed [doc] remove unnecessary notes about rebooting kernel (ref: #140) (#142) by @kbond
* ba7947c [minor] improve make:factory error message when no entities exist (#139) by @kbond
* 3812def [minor] use project var dir for test cache/logs (#139) by @kbond

## [v1.9.0](https://github.com/zenstruck/foundry/releases/tag/v1.9.0)

March 12th, 2021 - [v1.8.0...v1.9.0](https://github.com/zenstruck/foundry/compare/v1.8.0...v1.9.0)

* 0872be0 [doc] Add --test option as tip (#138) by @OskarStark
* f55afe2 [doc] Fix typos in the docs (#136) by @jdreesen
* 88c081e [minor] "require" explicitly configuring global auto_refresh_proxies (#131) by @kbond
* 632de3d [feature] throw exception during autorefresh if unsaved changes detected (#131) by @kbond
* 5318c7f [minor] deprecate instantiating Factory directly: (#134) by @kbond
* b7a7880 [feature] add AnonymousFactory (#134) by @kbond
* 228895f [minor] add dev stability to ci matrix (#133) by @kbond
* b759712 [minor] explicitly add sqlite extension for gh actions (#130) by @kbond
* 1f332ed [bug] Fix exception message (#129) by @jdreesen
* 4bc80fb [minor] increase codecov threshold by @kbond

## [v1.8.0](https://github.com/zenstruck/foundry/releases/tag/v1.8.0)

February 27th, 2021 - [v1.7.1...v1.8.0](https://github.com/zenstruck/foundry/compare/v1.7.1...v1.8.0)

* 83d6b26 [feature] add ModelFactory::assert()/RepositoryProxy::assert() (#123) by @kbond
* a657d14 [feature] add ModelFactory::all()/find()/findBy() (#123) by @kbond
* ac775b9 [feature] add ModelFactory::count()/truncate() (#123) by @kbond
* 34373da [feature] add ModelFactory::first()/last() (#123) by @kbond
* 5978574 [minor] psalm fixes (#122) by @kbond
* 88cb7c9 [minor] add getCommandDescription() to Maker's (#121) by @kbond
* 31971e0 [minor] fail ci if direct deprecations (#121) by @kbond
* ecc0e10 [bug] bump min php version (fixes #118) (#119) by @kbond

## [v1.7.1](https://github.com/zenstruck/foundry/releases/tag/v1.7.1)

February 6th, 2021 - [v1.7.0...v1.7.1](https://github.com/zenstruck/foundry/compare/v1.7.0...v1.7.1)

* 6bab709 [bug] fix unmanaged many-to-one entity problem (fixes #114) (#117) by @kbond
* b92a69a [minor] adjust cs-check gh action and fix cs (#116) by @kbond

## [v1.7.0](https://github.com/zenstruck/foundry/releases/tag/v1.7.0)

January 17th, 2021 - [v1.6.0...v1.7.0](https://github.com/zenstruck/foundry/compare/v1.6.0...v1.7.0)

* 9d42401 [feature] add attributes to ModelFactory/RepositoryProxy random methods (#112) by @kbond
* 149ea48 [feature] Remove "visual duplication" of ModelFactory::new()->create() (#111) by @wouterj
* 0c69967 [feature] Added ModelFactory::randomOrCreate() (#108) by @wouterj
* 574c246 [minor] use zenstruck/callback for Proxy::executeCallback() (#107) by @kbond
* 07f1ffe [minor] apply suggested psalm fix (#102) by @kbond
* e53b834 [minor] enable code coverage action to work with xdebug 3 (#99) by @kbond
* 6ea273b [minor] psalm-suppress InternalMethod (#99) by @kbond
* 1dbbab8 [minor] add codecov badge (#98) by @kbond
* 77f7ce0 [minor] switch to codecov by @kbond
* f269dc4 [minor] use ramsey/composer-install in static-analysis job (#97) by @kbond
* 0ca5479 [minor] Streamline GitHub CI by using ramsey/composer-install (#96) by @wouterj
* 8511d7a [minor] Re-enable Psalm and fixed annotations (#95) by @wouterj, @kbond

## [v1.6.0](https://github.com/zenstruck/foundry/releases/tag/v1.6.0)

December 7th, 2020 - [v1.5.0...v1.6.0](https://github.com/zenstruck/foundry/compare/v1.5.0...v1.6.0)

* c3f38d2 [minor] use local kernel instance in Factories and ResetDatabase traits (#92) by @kbond
* 88db502 [doc] document the need to create test client before factories (#92) by @kbond
* bf4d47a [bug] ensure foundry isn't rebooted in DatabaseReset (#92) by @kbond
* 1b6231a [bug] ensure kernel shutdown after ResetDatabase::_resetSchema() (#92) by @kbond
* 63c8eb7 [minor] disable psalm static analysis pending fix (#71) by @kbond
* e4d0a06 [doc] Added another relation example to Many-To-One (#93) by @weaverryan
* 596af47 [minor] support php8 (#71) by @kbond
* 2d574a5 [doc] Added docs for ModelFactory::new() (#91) by @Nyholm
* 6bd3195 [doc] Update link to faker (#90) by @Nyholm
* 338f6c8 [minor] Do not turn Psalm PHPdocs into comments (#85) by @wouterj
* dfc4388 [minor] Fixed issues found by Psalm level 4 (#85) by @wouterj
* 99aa22a [minor] Suppress nullable Psalm level 5 error (#85) by @wouterj
* e4ea180 [minor] Fixed issues found by Psalm level 6 (#85) by @wouterj
* 72c2e77 [minor] Added Psalm templated annotations (#85) by @wouterj
* 48572ce [minor] Installed Psalm and configured GitHub Workflow (#85) by @wouterj
* 0476572 [minor] Update docs with PHP file config (#87) by @TavoNiievez
* 66d0025 [minor] Use PHP CS Fixer udiff to only show snippets (#86) by @wouterj
* 8ecb162 [minor] Use consistent spacing in GitHub Actions config (#86) by @wouterj
* 7e90a05 [minor] Only run one build with prefer-lowest (#86) by @wouterj

## [v1.5.0](https://github.com/zenstruck/foundry/releases/tag/v1.5.0)

November 10th, 2020 - [v1.4.0...v1.5.0](https://github.com/zenstruck/foundry/compare/v1.4.0...v1.5.0)

* 3b36c9f [minor] deprecate using snake/kebab-cased attributes (#81) by @kbond
* 3ecd4f6 [minor] set min version of symfony/maker-bundle to 1.13.0 (#81) by @kbond
* 3c0e149 [minor] swap phpunit for phpunit-bridge mark deprecated tests (#81) by @kbond
* 4292d02 [bug] fix typo (#81) by @kbond
* de1fb41 [minor] trigger deprecations for other deprecated code (#81) by @kbond
* 5134347 [minor] deprecate "optional:" & "force:" attribute prefixes (#81) by @kbond
* def8ebc [feature] define extra attributes/forced properties on Instantiator (#81) by @kbond
* da504e0 [bug] boolean nodes should default to false instead of null (#83) by @kbond
* c376fef [minor] sort available entities (#80) by @wouterj
* d3a32cf [minor] add static return annotations (#79) by @micheh
* 77a3583 [minor] run test suite on PostgreSQL (#51) by @kbond
* 8db03c8 [minor] change faker lib used (#70) by @kbond
* 188eb63 [minor] disable dependabot by @kbond
* ea8fefe [minor] bump actions/cache from v1 to v2.1.2 (#69) by @dependabot[bot], dependabot[bot] <49699333+dependabot[bot]@users.noreply.github.com>
* bd5249d [minor] update shivammathur/setup-php requirement to 2.7.0 (#68) by @dependabot[bot], dependabot[bot] <49699333+dependabot[bot]@users.noreply.github.com>
* 800fae7 [minor] update actions/checkout requirement to v2.3.3 (#67) by @dependabot[bot], dependabot[bot] <49699333+dependabot[bot]@users.noreply.github.com>
* 0252e90 [minor] add dependabot for github actions by @kbond
* 537b2fd [bug] RepositoryProxy::findOneBy() with $orderBy checks inner repo (#66) by @kbond
* 9302855 [minor] RepositoryProxy::truncate() compatible with any ObjectManager (#66) by @kbond
* 9fe12e5 [minor] have RepositoryProxy implement \Countable & \IteratorAggregate (#66) by @kbond
* 1c43645 [feature] improve RespositoryProxy::first() and add last() (#64) by @mpiot
* f9418ac [bug] add $orderBy param to RepositoryProxy::findOneBy() (#63) by @mpiot

## [v1.4.0](https://github.com/zenstruck/foundry/releases/tag/v1.4.0)

October 20th, 2020 - [v1.3.0...v1.4.0](https://github.com/zenstruck/foundry/compare/v1.3.0...v1.4.0)

* d5cab62 [doc] fixes (#59) by @kbond
* 4eb546c [doc] document non-Kernel testing (#59) by @kbond
* c8040f7 [doc] document using in PHPUnit data providers (#59) by @kbond
* fce3610 [minor] throw helpful exception if creating service factories w/o boot (#59) by @kbond
* a904e41 [minor] throw helpful exception if using service factories w/o bundle (#59) by @kbond
* 1efc502 [minor] throw helpful exception if using service stories without bundle (#59) by @kbond
* a5d4154 [feature] remove ->withoutPersisting() requirement in pure unit tests (#59) by @kbond
* aef9123 [feature] allow Factories trait to be used in pure units tests (#59) by @kbond
* 732e616 [minor] deprecate TestState::withoutBundle() (#59) by @kbond
* d2c8b47 [bug] ensure Foundry is "shutdown" after each test (#59) by @kbond
* f3cc0c3 [bug] allow model factories to be created in dataProviders (#59) by @kbond
* b80d778 [doc] adding a link to SymfonyCasts (#60) by @weaverryan
* e37f492 [minor] add script to run all test configurations locally by @kbond

## [v1.3.0](https://github.com/zenstruck/foundry/releases/tag/v1.3.0)

October 14th, 2020 - [v1.2.1...v1.3.0](https://github.com/zenstruck/foundry/compare/v1.2.1...v1.3.0)

* fd433b1 [feature] allow factories to be defined as services (#53) by @kbond
* e686a08 [minor] remove dead debug code (#57) by @kbond
* aaf6ab4 [bug] fix typo in Factory stub (fixes #52) (#57) by @kbond

## [v1.2.1](https://github.com/zenstruck/foundry/releases/tag/v1.2.1)

October 12th, 2020 - [v1.2.0...v1.2.1](https://github.com/zenstruck/foundry/compare/v1.2.0...v1.2.1)

* ecf674a [doc] note that the ResetDatabase trait is required for global state by @kbond
* b748615 [minor] ensure coverage jobs use dama bundle (#48) by @kbond
* d4e3a2e [bug] sqlite does not support --if-exists (fixes #46) (#48) by @kbond
* 1138cf0 [minor] add sqlite tests (#48) by @kbond
* 91a6032 [minor] adjust github actions to use DATABASE_URL env var (#48) by @kbond

## [v1.2.0](https://github.com/zenstruck/foundry/releases/tag/v1.2.0)

October 8th, 2020 - [v1.1.4...v1.2.0](https://github.com/zenstruck/foundry/compare/v1.1.4...v1.2.0)

* e7b8481 [feature] add FactoryCollection object to help with relationships (#38) by @kbond

## [v1.1.4](https://github.com/zenstruck/foundry/releases/tag/v1.1.4)

October 7th, 2020 - [v1.1.3...v1.1.4](https://github.com/zenstruck/foundry/compare/v1.1.3...v1.1.4)

* 60e6881 [bug] allow RepositoryProxy::proxyResult() to handle doctrine proxies (#43) by @kbond

## [v1.1.3](https://github.com/zenstruck/foundry/releases/tag/v1.1.3)

September 28th, 2020 - [v1.1.2...v1.1.3](https://github.com/zenstruck/foundry/compare/v1.1.2...v1.1.3)

* 118186d [bug] ensure all attributes passed to afterPersist events (fixes #31) (#40) by @kbond
* f054e3c [bug] allow array callables in Proxy::executeCallback() (#39) by @kbond

## [v1.1.2](https://github.com/zenstruck/foundry/releases/tag/v1.1.2)

September 8th, 2020 - [v1.1.1...v1.1.2](https://github.com/zenstruck/foundry/compare/v1.1.1...v1.1.2)

* fb5b4ff [minor] run php-cs-fixer self-update (#33) by @kbond
* d734536 [minor] allow doctrine/persistence v2 (#33) by @kbond

## [v1.1.1](https://github.com/zenstruck/foundry/releases/tag/v1.1.1)

July 24th, 2020 - [v1.1.0...v1.1.1](https://github.com/zenstruck/foundry/compare/v1.1.0...v1.1.1)

* 91af450 [doc] better document without persisting usage (closes #22) (#27) by @kbond
* 03bce0d [minor] improve "foundry not booted" exception message (closes #24) (#28) by @kbond
* 2d7bc47 [doc] fix test example (closes #25) (#26) by @kbond
* 0a4fa3a Update README.md (#23) by @kbond
* 576858a [doc] fix typo (#20) by @jdreesen
* 331716a [doc] add packagist version badge by @kbond

## [v1.1.0](https://github.com/zenstruck/foundry/releases/tag/v1.1.0)

July 11th, 2020 - [v1.0.0...v1.1.0](https://github.com/zenstruck/foundry/compare/v1.0.0...v1.1.0)

* 7d91e42 [minor] change composer "type" by @kbond
* c01374a [BC BREAK] moved bundle to src root so it can be auto-configured by flex by @kbond

## [v1.0.0](https://github.com/zenstruck/foundry/releases/tag/v1.0.0)

July 10th, 2020 - _[Initial Release](https://github.com/zenstruck/foundry/commits/v1.0.0)_
