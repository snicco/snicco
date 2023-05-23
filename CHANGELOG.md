## [1.6.2](https://github.com/snicco/snicco/compare/v1.6.1...v1.6.2) (2023-05-23)


### Bug Fixes

* **better-wpdb:** don't convert keyset pagination query to lowercase ([3d7e441](https://github.com/snicco/snicco/commit/3d7e441ed24cbe76564f2ae58b52ba6c239c90cf)), closes [#182](https://github.com/snicco/snicco/issues/182)

## [1.6.1](https://github.com/snicco/snicco/compare/v1.6.0...v1.6.1) (2023-05-07)


### Bug Fixes

* **better-wp-cli-testing:** allow stream input as method argument ([36925a8](https://github.com/snicco/snicco/commit/36925a81812aca161f2400b85437a81c2342ff6e)), closes [#178](https://github.com/snicco/snicco/issues/178)
* **kernel:** assert that log dir is writable ([5d2a5dd](https://github.com/snicco/snicco/commit/5d2a5ddc678c2a4f7c2fd0a3d25883119824b709)), closes [#179](https://github.com/snicco/snicco/issues/179)

# [1.6.0](https://github.com/snicco/snicco/compare/v1.5.0...v1.6.0) (2023-02-03)


### Bug Fixes

* **kernel:** allow after_configuration callbacks from bootstrappers ([d17fc62](https://github.com/snicco/snicco/commit/d17fc620e398520c382ceb22a31936a4ca8ab1a1)), closes [#175](https://github.com/snicco/snicco/issues/175)


### Features

* **better-wp-cli:** allow to set custom set_error_handler function ([b94b1ea](https://github.com/snicco/snicco/commit/b94b1eae51d3098c72a2ad0145b3b70d3774fd2c)), closes [#176](https://github.com/snicco/snicco/issues/176)

# [1.5.0](https://github.com/snicco/snicco/compare/v1.4.2...v1.5.0) (2022-12-06)


### Bug Fixes

* **better-wp-cli:** use correct array keys during command registration ([febf531](https://github.com/snicco/snicco/commit/febf531c405d99e66c5fb754b9cd29b5d27821a1)), closes [#163](https://github.com/snicco/snicco/issues/163)
* **better-wp-hooks:** allow null as first arg for mapped filters ([47e505a](https://github.com/snicco/snicco/commit/47e505ab33448948d02debafafda9834a49c1ac8)), closes [#158](https://github.com/snicco/snicco/issues/158)
* **http-routing:** allow hosts without "." in url generation context ([c8ee18e](https://github.com/snicco/snicco/commit/c8ee18eeb14a54aa522a758ccf6f7d270dece1f1)), closes [#161](https://github.com/snicco/snicco/issues/161)
* **http-routing:** allow route files with the same name in different dirs ([7a34a60](https://github.com/snicco/snicco/commit/7a34a604439d2bb238505e3847d05572dd893a15))
* **signed-url:** add non-standard http ports to final url if passed ([1368c80](https://github.com/snicco/snicco/commit/1368c80ba1d08436867bfd85f2b7935c814380e7)), closes [#162](https://github.com/snicco/snicco/issues/162)
* **testing-bundle:** add "real" request method to test requests ([b063298](https://github.com/snicco/snicco/commit/b06329800e71e667c74359096b5c0306899fbc8c)), closes [#165](https://github.com/snicco/snicco/issues/165)


### Features

* **http-routing-bundle,testing-bundle:** multiple url prefixes can trigger early route-loading ([b95f50c](https://github.com/snicco/snicco/commit/b95f50ce6e4f692af8ccda27d4f872b66715c488)), closes [#164](https://github.com/snicco/snicco/issues/164)
* **templating,templating-bundle:** make parse length for parent views configurable ([7499eef](https://github.com/snicco/snicco/commit/7499eef985b5c7288769ffc9c6bd627781f215a8)), closes [#171](https://github.com/snicco/snicco/issues/171)
* validate bundle configuration after bootstrappers are configured ([7636cea](https://github.com/snicco/snicco/commit/7636cea897b3acabe353abacb4b20b172fa6b39d)), closes [#160](https://github.com/snicco/snicco/issues/160)
* **wp-nonce-middleware:** split middleware into two responsibilities ([0aa41b3](https://github.com/snicco/snicco/commit/0aa41b3f5618315e7a92a9d0b26707346a5d1434)), closes [#167](https://github.com/snicco/snicco/issues/167) [#159](https://github.com/snicco/snicco/issues/159)

## [1.4.2](https://github.com/snicco/snicco/compare/v1.4.1...v1.4.2) (2022-10-08)


### Bug Fixes

* **http-routing-bundle:** enforce request data is not slashed ([6be5136](https://github.com/snicco/snicco/commit/6be5136fb41caf4b0371e20ebaf58d443bd24d12))

## [1.4.1](https://github.com/snicco/snicco/compare/v1.4.0...v1.4.1) (2022-10-08)


### Bug Fixes

* **wp-nonce-middleware:** add nonce factory to all view responses ([4b67adb](https://github.com/snicco/snicco/commit/4b67adbe1ec611eaa744786fab1fbf0f9206c595)), closes [#155](https://github.com/snicco/snicco/issues/155)

# [1.4.0](https://github.com/snicco/snicco/compare/v1.3.0...v1.4.0) (2022-09-26)


### Bug Fixes

* **better-wp-cli:** fix negative count passed to str_repeat ([5f6e7a0](https://github.com/snicco/snicco/commit/5f6e7a0258b6f6c8c95c068aaf42c31ed057f53d)), closes [#152](https://github.com/snicco/snicco/issues/152)


### Features

* **better-wpdb:** add batch processing and keyset pagination ([db3f13e](https://github.com/snicco/snicco/commit/db3f13e395deabb1694b2a1cd63f9136db2acc70))
* **kernel:** add after configuration callbacks ([2794c59](https://github.com/snicco/snicco/commit/2794c59de8671f14a6640ac6192e29b84206223e)), closes [#128](https://github.com/snicco/snicco/issues/128)

# [1.3.0](https://github.com/snicco/snicco/compare/v1.2.1...v1.3.0) (2022-08-09)


### Bug Fixes

* **better-wpdb:** fix select lazy on php8+ ([caf21ef](https://github.com/snicco/snicco/commit/caf21efa58c44abc99c74a64aa9d5ba3bf271e54))
* **better-wpdb:** use unbuffered queries for lazy selects ([af829ef](https://github.com/snicco/snicco/commit/af829ef7d87880d9808bd4137d61196e2512b01c))
* **signed-url:** split identifier and selector at specific byte-length ([873f685](https://github.com/snicco/snicco/commit/873f68503680c3be9b4317a1eb90b5596fae1fcb))


### Features

* **better-wp-cli-testing:** initial implementation ([5c1e035](https://github.com/snicco/snicco/commit/5c1e035ab28be3d39b99043dcbcd38bb3ee4c254))
* **better-wp-cli:** add psr3 support ([fa72925](https://github.com/snicco/snicco/commit/fa72925663c694e7c0ead477c082128c07cdca56))
* **minimal-logger:** initial implementation ([95491f8](https://github.com/snicco/snicco/commit/95491f8dd4b956460043c8dcfd977d5ab3fab3af))

## [1.2.1](https://github.com/snicco/snicco/compare/v1.2.0...v1.2.1) (2022-05-28)


### Bug Fixes

* **testing-bundle:** fix request generation of test browser ([37806c2](https://github.com/snicco/snicco/commit/37806c29ed72e9a90265b3374088b43a97b6ed6f)), closes [#140](https://github.com/snicco/snicco/issues/140) [#141](https://github.com/snicco/snicco/issues/141)

# [1.2.0](https://github.com/snicco/snicco/compare/v1.1.3...v1.2.0) (2022-05-21)


### Features

* **signed-url-wp-bridge:** initial implementation ([637f228](https://github.com/snicco/snicco/commit/637f2289c1658487f1d16028b1bddce0e7f193d5))

## [1.1.3](https://github.com/snicco/snicco/compare/v1.1.2...v1.1.3) (2022-05-19)


### Bug Fixes

* **better-wpdb:** fix error handling reset with unbuffered queries ([5aa3ff8](https://github.com/snicco/snicco/commit/5aa3ff8e06fdd3213c03807c8cc7fa179084320c))

## [1.1.2](https://github.com/snicco/snicco/compare/v1.1.1...v1.1.2) (2022-05-16)


### Bug Fixes

* **signed-url:** fix issues with existing query strings ([7e53ce7](https://github.com/snicco/snicco/commit/7e53ce71d9caf42fafe7f1e8a73ab9200fa01f8b))

## [1.1.1](https://github.com/snicco/snicco/compare/v1.1.0...v1.1.1) (2022-05-06)


### Bug Fixes

* **http-routing:** dont include non-global middleware in cache ([b7a8751](https://github.com/snicco/snicco/commit/b7a875183935ca8e835e636fd334248762f896b2)), closes [#135](https://github.com/snicco/snicco/issues/135)

# [1.1.0](https://github.com/snicco/snicco/compare/v1.0.2...v1.1.0) (2022-05-02)


### Features

* **better-wp-cli:** allow retrieval of all input values ([9687a99](https://github.com/snicco/snicco/commit/9687a99867c17723df430832a407b53684e79405))
* **better-wp-cli:** initial release ([4a51b17](https://github.com/snicco/snicco/commit/4a51b17127b098fa09a1e60024e8b14376e0e24a))

## [1.0.2](https://github.com/snicco/snicco/compare/v1.0.1...v1.0.2) (2022-04-22)


### Bug Fixes

* **http-routing-bundle:** resolve exception transformer from container ([fec8baf](https://github.com/snicco/snicco/commit/fec8baf42143494b63487274a8abe5e2fbf1d9ef)), closes [#132](https://github.com/snicco/snicco/issues/132)
* **kernel:** throw logic exception for incorrect use of kernel callbacks ([4c188a7](https://github.com/snicco/snicco/commit/4c188a7b5547483fb831602436e9ec7247aea4e2)), closes [#128](https://github.com/snicco/snicco/issues/128)

## [1.0.1](https://github.com/snicco/snicco/compare/v1.0.0...v1.0.1) (2022-04-18)


### Bug Fixes

* remove locked phpunit/codeception versions ([3472d63](https://github.com/snicco/snicco/commit/3472d637adbc7098ac4e592a26ebc5c9e0e31b29))

# 1.0.0 (2022-04-17)


### Features

* initial release ([45bee49](https://github.com/snicco/snicco/commit/45bee49b5b40b93cf65419428f371861fbdc2a0d))
