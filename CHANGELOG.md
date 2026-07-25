# Changelog

## [0.8.0-alpha](https://github.com/sunchayn/nimbus/compare/v0.7.0-alpha...v0.8.0-alpha) (2026-07-25)


### Features

* **routes:** expand inline validation detection scope ([#112](https://github.com/sunchayn/nimbus/issues/112)) ([99c7822](https://github.com/sunchayn/nimbus/commit/99c7822f88207b9dfa5e76fb4438e253ef8a9530))

## [0.7.0-alpha](https://github.com/sunchayn/nimbus/compare/v0.6.2-alpha...v0.7.0-alpha) (2026-07-25)


### Features

* **core:** make nimbus installable as dev dependency ([#110](https://github.com/sunchayn/nimbus/issues/110)) ([e789cbb](https://github.com/sunchayn/nimbus/commit/e789cbb63965ff3753fcb95670bfd4335ab0548b))
* **relay:** auto-inject CSRF token if missing ([#109](https://github.com/sunchayn/nimbus/issues/109)) ([c0c6a3b](https://github.com/sunchayn/nimbus/commit/c0c6a3bbf3db639cde988be1172d1fde94c0e65f))
* **responses:** allow downloading responses  ([#105](https://github.com/sunchayn/nimbus/issues/105)) ([3e6fb35](https://github.com/sunchayn/nimbus/commit/3e6fb3579f8e0ac83eb9a8e8546195064df645c0)), closes [#91](https://github.com/sunchayn/nimbus/issues/91)


### Bug Fixes

* **relay:** remember me sometimes doesn't work ([#107](https://github.com/sunchayn/nimbus/issues/107)) ([cdd1e4e](https://github.com/sunchayn/nimbus/commit/cdd1e4e6e6953c56005ca97d5fd3744de879e0f4))
* **response:** properly render object references in `dd` response ([#108](https://github.com/sunchayn/nimbus/issues/108)) ([86e6447](https://github.com/sunchayn/nimbus/commit/86e64479ed32fcd593292384c9d7025ca3e2ba2f))
* **routes:** do not execute application code during route extraction ([#102](https://github.com/sunchayn/nimbus/issues/102)) ([260c58b](https://github.com/sunchayn/nimbus/commit/260c58b399f1be53cfe231c0f226c8c5b515264c))


### Maintenance Tasks

* **core:** tidy-up folder structure ([#106](https://github.com/sunchayn/nimbus/issues/106)) ([842fe91](https://github.com/sunchayn/nimbus/commit/842fe9140454f61566f438dcb213a6c2185ee43f))


### CI/CD

* remove release-please header ([#111](https://github.com/sunchayn/nimbus/issues/111)) ([5fc4387](https://github.com/sunchayn/nimbus/commit/5fc4387822ef40da0f44a1907510073a67f81c06))


### Code Refactoring

* **extractor:** revamp the AST manipulation core ([#104](https://github.com/sunchayn/nimbus/issues/104)) ([0af9998](https://github.com/sunchayn/nimbus/commit/0af9998c7088eb214216d042b52a57aafb34e2d8))

## [0.6.2-alpha](https://github.com/sunchayn/nimbus/compare/v0.6.1-alpha...v0.6.2-alpha) (2026-07-14)


### Bug Fixes

* breaking change with l13.14.0 ([#98](https://github.com/sunchayn/nimbus/issues/98)) ([e766991](https://github.com/sunchayn/nimbus/commit/e76699132966428895adbc05653122bb62f2d6b6))


### Maintenance Tasks

* remove storybook and cleanup ([#96](https://github.com/sunchayn/nimbus/issues/96)) ([fe3d8bd](https://github.com/sunchayn/nimbus/commit/fe3d8bd6dc71df149ec7376e1658b04eeb075c16))
* use consistent naming for FE files ([#97](https://github.com/sunchayn/nimbus/issues/97)) ([1204679](https://github.com/sunchayn/nimbus/commit/12046797b64280c3adb0d968ce31fa12f494889d))


### CI/CD

* periodically run php and e2e tests ([#101](https://github.com/sunchayn/nimbus/issues/101)) ([d12d3d4](https://github.com/sunchayn/nimbus/commit/d12d3d4a9bade61590a74b59659b589d3a366eec))
* update deps ([#100](https://github.com/sunchayn/nimbus/issues/100)) ([b4218b0](https://github.com/sunchayn/nimbus/commit/b4218b06d931fa0af5ade6b6dcc0ae7154f889b8))

## [0.6.1-alpha](https://github.com/sunchayn/nimbus/compare/v0.6.0-alpha...v0.6.1-alpha) (2026-04-06)


### Bug Fixes

* **ui:** properly update scroll masks on content change ([#90](https://github.com/sunchayn/nimbus/issues/90)) ([496bdce](https://github.com/sunchayn/nimbus/commit/496bdce8b62a16f4b60bde9f9499c966b5bc54b1))


### Maintenance Tasks

* **ui:** improve dark theme ([#89](https://github.com/sunchayn/nimbus/issues/89)) ([47a5eb3](https://github.com/sunchayn/nimbus/commit/47a5eb37e9160c04b9991ba18d88038357a4c0f9))


### CI/CD

* convert e2e bash scripts into console commands ([#87](https://github.com/sunchayn/nimbus/issues/87)) ([56bb780](https://github.com/sunchayn/nimbus/commit/56bb7805e0a3f2ecdf655617a440d09f3d3b1678))

## [0.6.0-alpha](https://github.com/sunchayn/nimbus/compare/v0.5.1-alpha...v0.6.0-alpha) (2026-04-05)


### Features

* **environment:** add support for env variables ([#73](https://github.com/sunchayn/nimbus/issues/73)) ([1cc64da](https://github.com/sunchayn/nimbus/commit/1cc64dacb8e36a441fb70622738b9c64409326a3))
* **routes:** support root level endpoints ([#80](https://github.com/sunchayn/nimbus/issues/80)) ([749bb95](https://github.com/sunchayn/nimbus/commit/749bb958ceed320fc7360f41bd56661204c35a2f))


### Maintenance Tasks

* **environment:** simplify how variables are resolved ([#75](https://github.com/sunchayn/nimbus/issues/75)) ([ecf4137](https://github.com/sunchayn/nimbus/commit/ecf4137aa8cde0e16533cbb0d66d6b7a901993b5))
* Laravel 13 support ([#76](https://github.com/sunchayn/nimbus/issues/76)) ([91f9c05](https://github.com/sunchayn/nimbus/commit/91f9c0500de9e75dd708399a8d9779f031e30043))
* **ui:** drop `crypto.randomUUID` in favor of normal `uuid` ([#78](https://github.com/sunchayn/nimbus/issues/78)) ([43cb77b](https://github.com/sunchayn/nimbus/commit/43cb77ba676529e5654827a29cbed8df78097847))


### CI/CD

* bump vulnerable js packages ([#83](https://github.com/sunchayn/nimbus/issues/83)) ([2f48ef0](https://github.com/sunchayn/nimbus/commit/2f48ef0fa5262540441d469409bd792eb6b75121))
* fix e2e job regression and checkout to the proper branch ([#84](https://github.com/sunchayn/nimbus/issues/84)) ([70cda6e](https://github.com/sunchayn/nimbus/commit/70cda6efcde1c8cb2fb125fd1d49234fc265ef48))
* ignore npm scripts in CI ([#82](https://github.com/sunchayn/nimbus/issues/82)) ([502b859](https://github.com/sunchayn/nimbus/commit/502b859f392b2be37f16a1c728d58871c0ccba51))


### Dependency Updates

* bump vitest to v4 ([#79](https://github.com/sunchayn/nimbus/issues/79)) ([fa59d79](https://github.com/sunchayn/nimbus/commit/fa59d79c0d7cf67d2cfca4b28ce45654c650532c))
* bump vue to 3.5 ([#77](https://github.com/sunchayn/nimbus/issues/77)) ([1735779](https://github.com/sunchayn/nimbus/commit/173577962549860585a154170a9454c054d18f4c))

## [0.5.1-alpha](https://github.com/sunchayn/nimbus/compare/v0.5.0-alpha...v0.5.1-alpha) (2026-02-18)


### Bug Fixes

* **core:** properly handle sub-folder installation ([#69](https://github.com/sunchayn/nimbus/issues/69)) ([dbf6869](https://github.com/sunchayn/nimbus/commit/dbf6869d5923ea7e9b24cecb19c9af33d276c161))

## [0.5.0-alpha](https://github.com/sunchayn/nimbus/compare/v0.4.1-alpha...v0.5.0-alpha) (2026-02-17)


### Features

* **routes:** search by route name and Operation ID ([#64](https://github.com/sunchayn/nimbus/issues/64)) ([775a880](https://github.com/sunchayn/nimbus/commit/775a8807040b02c9001b612ffb57570ed8445c3e))
* **ui:** show warning for requests with placeholders ([#66](https://github.com/sunchayn/nimbus/issues/66)) ([991a1b5](https://github.com/sunchayn/nimbus/commit/991a1b5710d44152bd5f09c365a995bd32f16b8c))


### Bug Fixes

* **ui:** add the open tabs rail back ([#62](https://github.com/sunchayn/nimbus/issues/62)) ([2b10034](https://github.com/sunchayn/nimbus/commit/2b10034f5919ff4abd8c4fa4da94c59f53c91852))
* **ui:** empty request body has accidental full height ([432adf6](https://github.com/sunchayn/nimbus/commit/432adf64d8fb9f103dfeaacb52090598b99a041a))
* **ui:** scrollable content masks in sidebar are not reactive ([#60](https://github.com/sunchayn/nimbus/issues/60)) ([b1213a5](https://github.com/sunchayn/nimbus/commit/b1213a582cf81c6200e2c7bd418040a4f39cd5ec))


### Maintenance Tasks

* `viewportChildTag` is not required ([c8069c8](https://github.com/sunchayn/nimbus/commit/c8069c87408b15a743776c51d0d06446a27448fa))
* explain how to workaround single-threaded servers ([#63](https://github.com/sunchayn/nimbus/issues/63)) ([1bd0c94](https://github.com/sunchayn/nimbus/commit/1bd0c94e78a8b93f69501cf4511cbd253452924c))
* refine readme ([#67](https://github.com/sunchayn/nimbus/issues/67)) ([034d6f3](https://github.com/sunchayn/nimbus/commit/034d6f3877c95c6f5e83b7f232de15ab3442e0a4))


### CI/CD

* adjust E2E env ([#65](https://github.com/sunchayn/nimbus/issues/65)) ([c725a1f](https://github.com/sunchayn/nimbus/commit/c725a1fb9f93e678cfa1da91582ff8b41cba650d))

## [0.4.1-alpha](https://github.com/sunchayn/nimbus/compare/v0.4.0-alpha...v0.4.1-alpha) (2026-02-02)


### Maintenance Tasks

* wiki and artificats cleanups ([#56](https://github.com/sunchayn/nimbus/issues/56)) ([e1fe4ee](https://github.com/sunchayn/nimbus/commit/e1fe4eefeb647cd7555bb5889926941c60f32d7f))

## [0.4.0-alpha](https://github.com/sunchayn/nimbus/compare/v0.3.0-alpha...v0.4.0-alpha) (2026-01-31)


### Features

* **client:** add `tabs` support ([#52](https://github.com/sunchayn/nimbus/issues/52)) ([8c02877](https://github.com/sunchayn/nimbus/commit/8c028773d8d402fd392a5dfd38ab6018fc9d22af))
* **export:** add shareable links ([#41](https://github.com/sunchayn/nimbus/issues/41)) ([2895a0d](https://github.com/sunchayn/nimbus/commit/2895a0ddc6ee6e3ff53132980ec05db34b754b59))
* **history:** add history viewer and rewind ([#38](https://github.com/sunchayn/nimbus/issues/38)) ([e1b844c](https://github.com/sunchayn/nimbus/commit/e1b844cee0d4ee2919d98b43c4e719261ae8e213))
* persist UI state ([#32](https://github.com/sunchayn/nimbus/issues/32)) ([8780a79](https://github.com/sunchayn/nimbus/commit/8780a79557e02e5a600439480c911ec7bce4aefd))
* **relay:** add transaction mode to requests ([#49](https://github.com/sunchayn/nimbus/issues/49)) ([aa99eac](https://github.com/sunchayn/nimbus/commit/aa99eacd2cbd71b1e96fd2ca5bf340e513cb6419))
* **relay:** render `dd()` responses properly ([#29](https://github.com/sunchayn/nimbus/issues/29)) ([e3b3370](https://github.com/sunchayn/nimbus/commit/e3b3370ebe0000575c98897ef1335d0e603a37ae))
* **routes:** add support for processing OpenAPI specs ([#50](https://github.com/sunchayn/nimbus/issues/50)) ([c015e62](https://github.com/sunchayn/nimbus/commit/c015e625339a55b2082fc94edbf2ed105e71b63f))
* **routes:** auto-select route variables on click ([#24](https://github.com/sunchayn/nimbus/issues/24)) ([8e05ce4](https://github.com/sunchayn/nimbus/commit/8e05ce4978a552871eb4074001e5f05a0b4f937a))
* **routes:** support multi-applications in config [breaking] ([#34](https://github.com/sunchayn/nimbus/issues/34)) ([a090c48](https://github.com/sunchayn/nimbus/commit/a090c484f8a55b0bd526e5e4c05f8a159e22061c))
* **routes:** support Spatie Data objects ([#23](https://github.com/sunchayn/nimbus/issues/23)) ([052cdde](https://github.com/sunchayn/nimbus/commit/052cddeca6c01def957db713a704034e59417c4f))


### Bug Fixes

* **config:** use normalized key for config ([#26](https://github.com/sunchayn/nimbus/issues/26)) ([32ef39e](https://github.com/sunchayn/nimbus/commit/32ef39ef8e4e01a5db9d8817be823f64a64ac167))
* **curl:** properly export get requests with body payload ([#25](https://github.com/sunchayn/nimbus/issues/25)) ([4adb5a1](https://github.com/sunchayn/nimbus/commit/4adb5a1bbf93752daf2891c982de5b1cf985557d))
* **curl:** properly wrap JSON payload ([#31](https://github.com/sunchayn/nimbus/issues/31)) ([8bdd510](https://github.com/sunchayn/nimbus/commit/8bdd510f179b0c86175557d41fbda2797a41a507))
* **relay:** don't crash request on corrupt cookies ([#48](https://github.com/sunchayn/nimbus/issues/48)) ([0a4d444](https://github.com/sunchayn/nimbus/commit/0a4d44448b12b785bedf76f60286be098899634e))
* **relay:** properly process plain text payload ([#39](https://github.com/sunchayn/nimbus/issues/39)) ([7b0af6f](https://github.com/sunchayn/nimbus/commit/7b0af6feff1bb29b96ab2da45b7e13c97212db4e))
* **relay:** properly relay request paramters ([#28](https://github.com/sunchayn/nimbus/issues/28)) ([64ef46a](https://github.com/sunchayn/nimbus/commit/64ef46a8a4311d1d958bb30a557748bbf9a8f0ea))
* **ui:** ui improvements follow up (tabs + state persistence + error viewer) ([#47](https://github.com/sunchayn/nimbus/issues/47)) ([9118ad6](https://github.com/sunchayn/nimbus/commit/9118ad6d20108334ec7d66354ef0a928a8b36441))
* work around l12 breaking change ([#43](https://github.com/sunchayn/nimbus/issues/43)) ([562b02d](https://github.com/sunchayn/nimbus/commit/562b02d207efc57c6b062be8bbd95c64165c8526))


### Maintenance Tasks

* improve code mirror theme consistency ([#46](https://github.com/sunchayn/nimbus/issues/46)) ([33e1ff0](https://github.com/sunchayn/nimbus/commit/33e1ff0c7256c50f3a0a18a9673b6e9732f93395))
* move roadmap to a discussion ([07f0106](https://github.com/sunchayn/nimbus/commit/07f0106b167275b52bdd8d46a003f8adb7f441b2))
* remove the status page ([#51](https://github.com/sunchayn/nimbus/issues/51)) ([bc43530](https://github.com/sunchayn/nimbus/commit/bc43530a9eb5ad54b63ef8051067e6d6a5004b72))
* setup E2E foundation ([#22](https://github.com/sunchayn/nimbus/issues/22)) ([dc1f76b](https://github.com/sunchayn/nimbus/commit/dc1f76b4a6cddff44f1dcababda96fca474d7003))
* update roadmap ([35f6632](https://github.com/sunchayn/nimbus/commit/35f663206fbd0c95b502c82f7812574f72583b91))


### CI/CD

* add automated release flow ([#35](https://github.com/sunchayn/nimbus/issues/35)) ([141724e](https://github.com/sunchayn/nimbus/commit/141724eb8479cb65d5b62c9266bf821127ab24c9))
* elevate release-please permission ([1eb1f14](https://github.com/sunchayn/nimbus/commit/1eb1f14416f0c1a73964d09d58597af89b1ef6fb))
* fix auth token issue ([#42](https://github.com/sunchayn/nimbus/issues/42)) ([6cff0e9](https://github.com/sunchayn/nimbus/commit/6cff0e9ebdf08539e49a12a46c61d8da677dc0a4))
* fix gha issues ([#40](https://github.com/sunchayn/nimbus/issues/40)) ([edd9b7b](https://github.com/sunchayn/nimbus/commit/edd9b7be4f51d0232f8c16919d8883f89a5acb94))
* fix lock file ([ac44e19](https://github.com/sunchayn/nimbus/commit/ac44e19dbaf9c1592e456cdd0ed074d993cb4a32))
* fix release-please workflow ([cbd690a](https://github.com/sunchayn/nimbus/commit/cbd690a272ec307d3a335997726e8ed4890290d3))
* make release please workflow more explicit ([7fa6a8e](https://github.com/sunchayn/nimbus/commit/7fa6a8e901de4dd72d07cb5caf102d42b4b155f4))
* mark builds as success when skipped ([#37](https://github.com/sunchayn/nimbus/issues/37)) ([f818eee](https://github.com/sunchayn/nimbus/commit/f818eee454b2be03d31b33afdc39cd25220cd2ed))
* properly configure release please ([35db665](https://github.com/sunchayn/nimbus/commit/35db6654b17cba2e194a8922a08d1a9079ad1c87))
* properly set required jobs ([#44](https://github.com/sunchayn/nimbus/issues/44)) ([106bba7](https://github.com/sunchayn/nimbus/commit/106bba75399b0f9d3211a762aa8a33cc327e6dde))
* run E2E tests with the current branch ([#27](https://github.com/sunchayn/nimbus/issues/27)) ([7b4c37d](https://github.com/sunchayn/nimbus/commit/7b4c37d10d07bf707cd6ef76ef0bd355f390981e))
* use a hook for the version ([a9e4e12](https://github.com/sunchayn/nimbus/commit/a9e4e121ad6b8e61bc6b6939cba3ebee21653627))


### Code Refactoring

* **schemas:** use standardized schema shapes ([#33](https://github.com/sunchayn/nimbus/issues/33)) ([78d91a3](https://github.com/sunchayn/nimbus/commit/78d91a39c136c850de8aba9e0f36a988fb210123))
* solidify the FE codebase and improve UI consistency ([#45](https://github.com/sunchayn/nimbus/issues/45)) ([35b9604](https://github.com/sunchayn/nimbus/commit/35b96042f06d4a49ef07762ea76af1123cecc936))

## Changelog
