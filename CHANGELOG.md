# Changelog

## [1.3.0](https://github.com/schaefersoft/laravel-llms-txt/compare/v1.2.2...v1.3.0) (2026-09-04)


### Features

* add Guzzle 8 support ([6480482](https://github.com/schaefersoft/laravel-llms-txt/commit/6480482848d494e3a7c79546b1d298d78b9faff5))
* add Guzzle 8 support ([9215c16](https://github.com/schaefersoft/laravel-llms-txt/commit/9215c168b7a1ebc5bd82e353fb1dc0a94a65ffbd))

## [1.2.2](https://github.com/schaefersoft/laravel-llms-txt/compare/v1.2.1...v1.2.2) (2026-08-25)


### Miscellaneous Chores

* **dist:** include license file ([e8b04e7](https://github.com/schaefersoft/laravel-llms-txt/commit/e8b04e70ac11bb39c71c6ca4ebe7036b6c868c09))
* **dist:** keep unnecessary artefacts out of releases ([15b6c6d](https://github.com/schaefersoft/laravel-llms-txt/commit/15b6c6d7e9cdfbc030e9af9a97e4cd0814ef9cd0))
* **dist:** keep unnecessary artefacts out of releases ([95927f1](https://github.com/schaefersoft/laravel-llms-txt/commit/95927f1d9d4e598469bf83e9baae481a2877c74e))

## [1.2.1](https://github.com/schaefersoft/laravel-llms-txt/compare/v1.2.0...v1.2.1) (2026-08-04)


### Bug Fixes

* bump guzzle to patched version resolving security advisories ([fc844a5](https://github.com/schaefersoft/laravel-llms-txt/commit/fc844a5f02f852410f9683eca11d95331533736e))
* bump guzzle to patched version resolving security advisories ([0979889](https://github.com/schaefersoft/laravel-llms-txt/commit/0979889bdb1ab0d4f4a9c6820da470486066e0a9))

## [1.2.0](https://github.com/schaefersoft/laravel-llms-txt/compare/v1.1.0...v1.2.0) (2026-07-02)


### Features

* add exclude_routes config for the auto resolver ([8ae6802](https://github.com/schaefersoft/laravel-llms-txt/commit/8ae68023ef2ebcbec3c332b0fa13ab9dc1b25634))
* disable the llms-full.txt route by default ([ffec396](https://github.com/schaefersoft/laravel-llms-txt/commit/ffec396097276a36175f55e6bfdb6d05f9cfacba))
* flush all cache keys and add llms:clear command ([8431245](https://github.com/schaefersoft/laravel-llms-txt/commit/84312452aee85416ffc0965c400bb2db171ae5db))
* write generated files to the public folder by default ([0f06fec](https://github.com/schaefersoft/laravel-llms-txt/commit/0f06fece8a435e8e7e5ab3660a3f07288bdd45ef))


### Bug Fixes

* make LlmsTxt::routes() respect route_enabled ([e0265a8](https://github.com/schaefersoft/laravel-llms-txt/commit/e0265a80003a333481d3f03afe9d5efc9baea06d))

## [1.1.0](https://github.com/schaefersoft/laravel-llms-txt/compare/v1.0.3...v1.1.0) (2026-05-13)


### Features

* **core:** api restructure ([715447d](https://github.com/schaefersoft/laravel-llms-txt/commit/715447d43e4ed723a2ec47742e21601bc959de02))
* **core:** auto route resolver ([93cca25](https://github.com/schaefersoft/laravel-llms-txt/commit/93cca251875825881fc99ca81b37ac62dcadea71))
* **core:** entry helper method for description ([927b521](https://github.com/schaefersoft/laravel-llms-txt/commit/927b521be4cf5c6b14afd0f179aa09efd71e6863))
* **core:** llmstxt helper make alias and when conditional ([d441367](https://github.com/schaefersoft/laravel-llms-txt/commit/d441367213356eeac0573cb7bccd9d74daf7f380))
* **core:** section helpers for entry and when methods ([40637fb](https://github.com/schaefersoft/laravel-llms-txt/commit/40637fbda93d41394e4c0009895f43941318c73c))


### Bug Fixes

* **core:** route registration ([b5a4d20](https://github.com/schaefersoft/laravel-llms-txt/commit/b5a4d20cca9cc8cde34607275d8eb92b8a0075dd))


### Miscellaneous Chores

* laravel 13 and php 8.5 tests ([e1a3b42](https://github.com/schaefersoft/laravel-llms-txt/commit/e1a3b424dfa5dd43c15f756bab10cf74e5f9fae6))
* laravel 13 and php 8.5 tests (fix 2) ([8f400fd](https://github.com/schaefersoft/laravel-llms-txt/commit/8f400fd6a4c72d2b59e169d0523445ae7ed2e3c5))
* laravel 13 and php 8.5 tests (fix 3) ([49eb01c](https://github.com/schaefersoft/laravel-llms-txt/commit/49eb01cf633454f534108353e99ef71ea751f8a1))
* laravel 13 and php 8.5 tests (fix) ([2637995](https://github.com/schaefersoft/laravel-llms-txt/commit/263799584a77a97c5c976f60ad874ec497d08aa2))
* pint cleanup ([d8ad0e5](https://github.com/schaefersoft/laravel-llms-txt/commit/d8ad0e5a2adbbb002bf67c7937a75af4f0ad2899))

## [1.0.3](https://github.com/schaefersoft/laravel-llms-txt/compare/v1.0.2...v1.0.3) (2026-03-26)


### Bug Fixes

* **core:** localized cache keys ([b97d315](https://github.com/schaefersoft/laravel-llms-txt/commit/b97d315e23c8956a512d867f6b1252782d8dff74))

## [1.0.2](https://github.com/schaefersoft/laravel-llms-txt/compare/v1.0.1...v1.0.2) (2026-03-26)


### Bug Fixes

* **core:** build errors ([51b7ed6](https://github.com/schaefersoft/laravel-llms-txt/commit/51b7ed6d58c78d08b8b37557bcb61f88b1617b8f))

## [1.0.1](https://github.com/schaefersoft/laravel-llms-txt/compare/v1.0.0...v1.0.1) (2026-03-26)


### Bug Fixes

* **core:** fix http route registration for locales ([1f0fab9](https://github.com/schaefersoft/laravel-llms-txt/commit/1f0fab93f28d589d1f07d167752c1cd96a790667))
* **core:** fix http route registration for route:caching ([ba92bd9](https://github.com/schaefersoft/laravel-llms-txt/commit/ba92bd9d8465e2634142a2f8cea953163dc5bc77))

## 1.0.0 (2026-03-26)


### Features

* initial package release ([cc494a8](https://github.com/schaefersoft/laravel-llms-txt/commit/cc494a813cd884edee1702be2e2ce1cf9b04fb2b))
