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
