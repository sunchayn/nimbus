# Changelog

## [1.0.0](https://github.com/sunchayn/nimbus/compare/v0.9.0-alpha...v1.0.0) (2026-11-14)


### Features

* **client:** add a rate limit meter and a limiter reset ([#192](https://github.com/sunchayn/nimbus/issues/192)) ([4c203de](https://github.com/sunchayn/nimbus/commit/4c203de1dd3b238c2e3a832290a914fd59223dfc))
* **client:** add a request and response `plain` tab ([#167](https://github.com/sunchayn/nimbus/issues/167)) ([9abd32a](https://github.com/sunchayn/nimbus/commit/9abd32ac36812342a576fd8a76bcf028b286f360))
* **client:** add dynamic variables with suggestions ([#170](https://github.com/sunchayn/nimbus/issues/170)) ([d0b60ef](https://github.com/sunchayn/nimbus/commit/d0b60efefb1a2ad3ee044bb39f55540b76442efe))
* **client:** add response search and a pretty toggle ([#171](https://github.com/sunchayn/nimbus/issues/171)) ([cffee0f](https://github.com/sunchayn/nimbus/commit/cffee0f33be5bb77365f979f0eb02311d44fdab8))
* **client:** add route filters and history pinning ([#182](https://github.com/sunchayn/nimbus/issues/182)) ([c79938f](https://github.com/sunchayn/nimbus/commit/c79938f0dd10d9cab49f86959f7fd814c6cd1cfb))
* **client:** add urlencoded, html, xml and binary body types ([#169](https://github.com/sunchayn/nimbus/issues/169)) ([76bad19](https://github.com/sunchayn/nimbus/commit/76bad19d732baf93ab592ef59aafd9b889287b95))
* **client:** copy a request as code ([#172](https://github.com/sunchayn/nimbus/issues/172)) ([d46806e](https://github.com/sunchayn/nimbus/commit/d46806ead9aec729e79e716b008a052903483a33))
* **doctor:** add the nimbus:doctor command and a diagnostics view ([#164](https://github.com/sunchayn/nimbus/issues/164)) ([f917047](https://github.com/sunchayn/nimbus/commit/f9170476daba22aa3bc017bc06852fae91cbff50))
* **extractor:** add query parameters read by get controllers ([#179](https://github.com/sunchayn/nimbus/issues/179)) ([ef960de](https://github.com/sunchayn/nimbus/commit/ef960de061c91cad2070ebcbc16e88cb5f6f44c7))
* **extractor:** cover more validation rules and label conditional fields ([#175](https://github.com/sunchayn/nimbus/issues/175)) ([ef52038](https://github.com/sunchayn/nimbus/commit/ef520387b87b3346eb98c56bb1862dc1b6d21391))
* **extractor:** read pagination envelopes and optional keys ([#177](https://github.com/sunchayn/nimbus/issues/177)) ([8fc2654](https://github.com/sunchayn/nimbus/commit/8fc26541cb3ce89d51c1a099b847336a365d4e9e))
* **extractor:** read rule objects and form request hooks ([#178](https://github.com/sunchayn/nimbus/issues/178)) ([9d95b20](https://github.com/sunchayn/nimbus/commit/9d95b20e26c22f1a0e7d131f09045154d062b689))
* **insight:** collect what a relayed request did ([#195](https://github.com/sunchayn/nimbus/issues/195)) ([87f3d47](https://github.com/sunchayn/nimbus/commit/87f3d47769b50de8386f732fe97e2464b20c8264))
* **insight:** show what a request did in an insight tab ([#196](https://github.com/sunchayn/nimbus/issues/196)) ([054c934](https://github.com/sunchayn/nimbus/commit/054c9344d258f3c44184178ea6b715653e03936e))
* **magic:** add a sign and send action for signed routes ([#181](https://github.com/sunchayn/nimbus/issues/181)) ([f72f106](https://github.com/sunchayn/nimbus/commit/f72f10619b303d9b3a6bd979abe90a77dc5826b5))
* **magic:** add sandbox mode for mail, notifications, jobs and events ([#197](https://github.com/sunchayn/nimbus/issues/197)) ([895440f](https://github.com/sunchayn/nimbus/commit/895440ff70db9c044ed39e415651573f2ece4f42))
* **magic:** authorize with a personal access token for a user ([#190](https://github.com/sunchayn/nimbus/issues/190)) ([7be4ca6](https://github.com/sunchayn/nimbus/commit/7be4ca6756e42fa583715d2e36d8cd970ffabc52))
* **magic:** impersonate through an unsigned actor header ([#187](https://github.com/sunchayn/nimbus/issues/187)) ([6ed0436](https://github.com/sunchayn/nimbus/commit/6ed0436a8d91435f3b0e018068e3b72adaa716b6))
* **magic:** pick the impersonated user from a searchable list ([#188](https://github.com/sunchayn/nimbus/issues/188)) ([4eebeff](https://github.com/sunchayn/nimbus/commit/4eebeffe663bd9a1a26f3f0675a267e71d06f38e))
* **magic:** prefetch existing values for exists rules ([#183](https://github.com/sunchayn/nimbus/issues/183)) ([391291f](https://github.com/sunchayn/nimbus/commit/391291f7c3952ddb79329ae297ed08efab468eec))
* **magic:** roll back transactions on every connection ([#186](https://github.com/sunchayn/nimbus/issues/186)) ([f1982aa](https://github.com/sunchayn/nimbus/commit/f1982aaa2435f5827b39867f7198696ad22c2ce3))
* **magic:** run a request at a chosen time ([#189](https://github.com/sunchayn/nimbus/issues/189)) ([e78f561](https://github.com/sunchayn/nimbus/commit/e78f56184edf43be4962032332bc39139fc6c92b))
* **routes:** keep route metadata and show auth and throttle hints ([#176](https://github.com/sunchayn/nimbus/issues/176)) ([ad08c6c](https://github.com/sunchayn/nimbus/commit/ad08c6c7fecd8f80113bc7dd4c5cae490e696274))
* **toolbox:** add a dialog with Laravel helpers ([#191](https://github.com/sunchayn/nimbus/issues/191)) ([8112124](https://github.com/sunchayn/nimbus/commit/8112124507735cdd9d11010002cd99abadc2602d))
* **ui:** add a command palette and keyboard shortcuts ([#165](https://github.com/sunchayn/nimbus/issues/165)) ([0bfdfcd](https://github.com/sunchayn/nimbus/commit/0bfdfcdb62cc9779e36a9fb08f8dd3b637c924d5))


### Bug Fixes

* **client:** keep browser storage within its quota ([#168](https://github.com/sunchayn/nimbus/issues/168)) ([e09109d](https://github.com/sunchayn/nimbus/commit/e09109d4e92103d4e0576c34fded49067b6fce24))
* exclude `staging` from default allowed envs ([#201](https://github.com/sunchayn/nimbus/issues/201)) ([a88a686](https://github.com/sunchayn/nimbus/commit/a88a686cde971e1a781f484a1a5d258bfc5b5308))
* **intellisense:** skip unchanged files and fail CI on stale output ([#194](https://github.com/sunchayn/nimbus/issues/194)) ([a9391a4](https://github.com/sunchayn/nimbus/commit/a9391a4d6a816c24684f2226149c842429be72ca))
* **relay:** add request options and match multipart by media type ([#166](https://github.com/sunchayn/nimbus/issues/166)) ([2f7d42d](https://github.com/sunchayn/nimbus/commit/2f7d42d9d0dca1ce333c91a1a7e2751300817d36))
* **ui:** let menu items grow past the row height ([#174](https://github.com/sunchayn/nimbus/issues/174)) ([4a1a618](https://github.com/sunchayn/nimbus/commit/4a1a618cd544ae464e21e3409075e11548984bd6))


### Code Refactoring

* **magic:** move transaction listener and injectors into a magic module ([#180](https://github.com/sunchayn/nimbus/issues/180)) ([467d4a3](https://github.com/sunchayn/nimbus/commit/467d4a3644bac3bff90a9690d57e50a1563d77a1))


### Build System

* auto deploy releases to the demo website ([#163](https://github.com/sunchayn/nimbus/issues/163)) ([aeb9556](https://github.com/sunchayn/nimbus/commit/aeb955649062ddcd0c716cfc40c148701f3d0f55))
* bump invade package ([#200](https://github.com/sunchayn/nimbus/issues/200)) ([7867ec6](https://github.com/sunchayn/nimbus/commit/7867ec6b236a1e16b590ead523f8d46e0b018b07))


### Maintenance

* add laravel versions badge ([#160](https://github.com/sunchayn/nimbus/issues/160)) ([dd8a456](https://github.com/sunchayn/nimbus/commit/dd8a456143cbfa790813290c1ccc86e503313f8f))
* repo housekeeping after core deps update ([#199](https://github.com/sunchayn/nimbus/issues/199)) ([10a124e](https://github.com/sunchayn/nimbus/commit/10a124e40c7902d124647ec55cf5db071ccacfcc))
* **ui:** fixes after client and extractor stacks merged ([#185](https://github.com/sunchayn/nimbus/issues/185)) ([2137abd](https://github.com/sunchayn/nimbus/commit/2137abd20dd62ff84106642c92a77aa1f4bafe17))

## [0.9.0-alpha](https://github.com/sunchayn/nimbus/compare/v0.8.0-alpha...v0.9.0-alpha) (2026-11-06)


### Features

* **client:** accept a pasted full URL in the endpoint ([#157](https://github.com/sunchayn/nimbus/issues/157)) ([8875030](https://github.com/sunchayn/nimbus/commit/88750305e009e11c3294056dd784beb2d132233e))
* **client:** improve JSON uploads autofilling ([#141](https://github.com/sunchayn/nimbus/issues/141)) ([f648e2e](https://github.com/sunchayn/nimbus/commit/f648e2e63e7971fa030ce584fbaa30d1ea8b8779))
* **envs:** use environments as request targets ([#136](https://github.com/sunchayn/nimbus/issues/136)) ([5c6abc0](https://github.com/sunchayn/nimbus/commit/5c6abc0926719fe581fbfe48f10706ead37b042d))
* **relay:** add support for file uploads ([#138](https://github.com/sunchayn/nimbus/issues/138)) ([f92c139](https://github.com/sunchayn/nimbus/commit/f92c13948195f8d4e2854619d74439255174462d))
* **relay:** forward download responses ([#130](https://github.com/sunchayn/nimbus/issues/130)) ([ba2b949](https://github.com/sunchayn/nimbus/commit/ba2b94963805574c34c7d2e75d60bf0ffccd5285))
* **routes:** support extraction snapshots ([#128](https://github.com/sunchayn/nimbus/issues/128)) ([3a8b1e4](https://github.com/sunchayn/nimbus/commit/3a8b1e41d59939c837e9ba6ac332be48f9b00526))
* **routes:** support nested folders in routes list ([#114](https://github.com/sunchayn/nimbus/issues/114)) ([498e37c](https://github.com/sunchayn/nimbus/commit/498e37cc80088421709e9bbdb6b3b7e40008f1e0))
* **ui:** add loading shell ([#127](https://github.com/sunchayn/nimbus/issues/127)) ([29a102b](https://github.com/sunchayn/nimbus/commit/29a102baaba6bf8fcad8adb33ef403e661de7d5c))
* **ui:** add open tabs contextual actions ([#125](https://github.com/sunchayn/nimbus/issues/125)) ([36712fd](https://github.com/sunchayn/nimbus/commit/36712fd30a4efd9248691afaad5956a11d118d2c))
* **ui:** add solarized and sakura themes ([#140](https://github.com/sunchayn/nimbus/issues/140)) ([9e5f453](https://github.com/sunchayn/nimbus/commit/9e5f453408dae54020d658e1d05b057f3bf0ca01))
* **ui:** allow multiple open tabs for same endpoint ([#131](https://github.com/sunchayn/nimbus/issues/131)) ([9047457](https://github.com/sunchayn/nimbus/commit/90474579ac6abcfc9c0ba1f8f3470da625e98287))
* **ui:** revamp theme switcher ([#126](https://github.com/sunchayn/nimbus/issues/126)) ([744be23](https://github.com/sunchayn/nimbus/commit/744be23744d6faa6e560d5712ae1449452096803))
* **ui:** support wrappable views ([#146](https://github.com/sunchayn/nimbus/issues/146)) ([7d29be6](https://github.com/sunchayn/nimbus/commit/7d29be6d7f2b8c5f62d09242774423142eeda71d))


### Bug Fixes

* **client:** cancel the request when leaving its tab ([#148](https://github.com/sunchayn/nimbus/issues/148)) ([e65e4f1](https://github.com/sunchayn/nimbus/commit/e65e4f1a66575e0bb5fd35ffbee4479a7b365045))
* **client:** cap request history by size ([#155](https://github.com/sunchayn/nimbus/issues/155)) ([c9850d5](https://github.com/sunchayn/nimbus/commit/c9850d5f1c649a48d5a8506a046c54d9d85e4039))
* **client:** keep the Content-Type header the user set ([#150](https://github.com/sunchayn/nimbus/issues/150)) ([94f64a6](https://github.com/sunchayn/nimbus/commit/94f64a6a0086b1ba34388cddbab046b11a315e88))
* **client:** recover from stale saved state and bad share links ([#149](https://github.com/sunchayn/nimbus/issues/149)) ([36ae0c1](https://github.com/sunchayn/nimbus/commit/36ae0c1b3b64b2bbe817bd31784d2b6c85e05e98))
* **client:** refresh open tabs schemas when routes reload ([#142](https://github.com/sunchayn/nimbus/issues/142)) ([7c964e3](https://github.com/sunchayn/nimbus/commit/7c964e3daf93432430eb68f5507c122561cdb04b))
* **generator:** restructure how generators work and fix bugs ([#139](https://github.com/sunchayn/nimbus/issues/139)) ([7f1d317](https://github.com/sunchayn/nimbus/commit/7f1d3177dd842f47f4daf1f824ca2acc5efef30e))
* **relay:** validate relay input and shorten connection errors ([#151](https://github.com/sunchayn/nimbus/issues/151)) ([70c7d32](https://github.com/sunchayn/nimbus/commit/70c7d324286606ee92854d5032a3939465842962))
* routes explorer verbs spacing ([#135](https://github.com/sunchayn/nimbus/issues/135)) ([8e19594](https://github.com/sunchayn/nimbus/commit/8e1959401e4d526ae57b95bbe6f4ec25f5beb018))
* **state:** properly restore form data from state ([#137](https://github.com/sunchayn/nimbus/issues/137)) ([9fbbcf6](https://github.com/sunchayn/nimbus/commit/9fbbcf6d80cc45d810cdaff3456ce77cdeab20d8))
* **ui:** adjust invalid popup paddings ([#145](https://github.com/sunchayn/nimbus/issues/145)) ([aea148a](https://github.com/sunchayn/nimbus/commit/aea148abe792a4fee0ba5e72a2918d4d2c2829e0))
* **ui:** broken content alignment for open tabs ([#124](https://github.com/sunchayn/nimbus/issues/124)) ([6642d34](https://github.com/sunchayn/nimbus/commit/6642d34b211f570d49fe4fe9c6da42b04b3255f2))
* **ui:** improve theme switcher radio button ([#133](https://github.com/sunchayn/nimbus/issues/133)) ([3699bda](https://github.com/sunchayn/nimbus/commit/3699bdad9a4e838935dd00e2ff55e5661f0bd214))
* **ui:** keep the key-value toolbar visible in short panels ([#154](https://github.com/sunchayn/nimbus/issues/154)) ([8492a2f](https://github.com/sunchayn/nimbus/commit/8492a2f202cd4f4c2c5cbd5b8ad86ce4390e7221))
* **ui:** let tab lists scroll with a drag or the mouse wheel ([#156](https://github.com/sunchayn/nimbus/issues/156)) ([8b40f43](https://github.com/sunchayn/nimbus/commit/8b40f4317012a89789b03554e823feb3d62daf43))
* **ui:** polish control sizing and long environment names ([#152](https://github.com/sunchayn/nimbus/issues/152)) ([a09bd86](https://github.com/sunchayn/nimbus/commit/a09bd86b7735392c2ffb43d7c4ff64cc32bd91d2))
* **ui:** properly save state after collapsing sidebar ([#123](https://github.com/sunchayn/nimbus/issues/123)) ([b75416f](https://github.com/sunchayn/nimbus/commit/b75416fe17b5d4ec43bcbc1d7d9d0667246868ff))
* **ui:** restore the status indicator glow ([#159](https://github.com/sunchayn/nimbus/issues/159)) ([d452053](https://github.com/sunchayn/nimbus/commit/d452053373b511f8372ee8f989f616c8dec45530))
* **ui:** rewind history in a new tab ([#132](https://github.com/sunchayn/nimbus/issues/132)) ([a1a8843](https://github.com/sunchayn/nimbus/commit/a1a8843196542d0a4e8792d9762a37674c2137d0))
* **ui:** tab rename lose focus if opened via right-click ([#129](https://github.com/sunchayn/nimbus/issues/129)) ([265977f](https://github.com/sunchayn/nimbus/commit/265977fbdb937d157c61d4cb6fa476df9a7e7d64))
* **ui:** use one focus ring style everywhere ([#153](https://github.com/sunchayn/nimbus/issues/153)) ([ddf5c09](https://github.com/sunchayn/nimbus/commit/ddf5c093c592b0fc5bc95a10a41d72666802e4ba))


### Code Refactoring

* **core:** introduce a polished Nimbus UI  ([#119](https://github.com/sunchayn/nimbus/issues/119)) ([8293a7b](https://github.com/sunchayn/nimbus/commit/8293a7b8233473b0fd103ec2fe5197a600706876))


### Maintenance

* add AI instructions ([#115](https://github.com/sunchayn/nimbus/issues/115)) ([6e6fd14](https://github.com/sunchayn/nimbus/commit/6e6fd1489417616bdfe86a81235ad77cadc77e24))
* generalize design system tokens ([#122](https://github.com/sunchayn/nimbus/issues/122)) ([2181c4f](https://github.com/sunchayn/nimbus/commit/2181c4f2b51852627fef97872de5e9d141be170f))
* improve release-please config ([#117](https://github.com/sunchayn/nimbus/issues/117)) ([6f632f5](https://github.com/sunchayn/nimbus/commit/6f632f516a7af7038b792347e375eaf4d53be4c4))
* repo housekeeping ([#118](https://github.com/sunchayn/nimbus/issues/118)) ([5f30cd9](https://github.com/sunchayn/nimbus/commit/5f30cd9b593bbf2dc7ee5b156f8f3d8be0cf8f7e))
* **ui:** globalize documentation link ([#134](https://github.com/sunchayn/nimbus/issues/134)) ([423c075](https://github.com/sunchayn/nimbus/commit/423c075138d1b01bc2bba6d819ba72eec240558b))
* **ui:** improve extraction error design ([#144](https://github.com/sunchayn/nimbus/issues/144)) ([224f7d9](https://github.com/sunchayn/nimbus/commit/224f7d94f56d711c40215c3df569c1c403443492))
* **ui:** improvements after revamp ([#121](https://github.com/sunchayn/nimbus/issues/121)) ([75bcd31](https://github.com/sunchayn/nimbus/commit/75bcd313fbdebe1d7e9c6f36c43d33551d0a1b56))
* use workbench in place of a dev repo ([#120](https://github.com/sunchayn/nimbus/issues/120)) ([f5193c6](https://github.com/sunchayn/nimbus/commit/f5193c65f8a57a1d3a5b5af97e67acedb1888e4e))

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
