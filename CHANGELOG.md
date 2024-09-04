# [2.0.0-beta.7](https://github.com/snicco/snicco/compare/v2.0.0-beta.6...v2.0.0-beta.7) (2024-09-04)


### Bug Fixes

* **kernel:** use namespaced bootstrap cache key ([ae9b817](https://github.com/snicco/snicco/commit/ae9b817c77de828802581d7559be48dce6bffd2c))

# [2.0.0-beta.6](https://github.com/snicco/snicco/compare/v2.0.0-beta.5...v2.0.0-beta.6) (2024-09-03)


### Bug Fixes

* remove renamed/deleted files before splitting the package ([9df6f27](https://github.com/snicco/snicco/commit/9df6f27e3b77c00c1e95afbee96631c589636399))

# [2.0.0-beta.5](https://github.com/snicco/snicco/compare/v2.0.0-beta.4...v2.0.0-beta.5) (2024-09-03)


### Bug Fixes

* **http-routing-bundle:** use kernel bootstrap cache for routes and middleware ([611d210](https://github.com/snicco/snicco/commit/611d210246797b4ce8f026473c0dbdc30ce54405))
* **http-routing:** remove file route cache class ([5677aa2](https://github.com/snicco/snicco/commit/5677aa2c2679743b198bbaf54aa3f78ffbe5e460))


### Features

* **kernel:** accept cache keys in bootstrap cache instead of files ([d3162e1](https://github.com/snicco/snicco/commit/d3162e106f8a2335c682ea2c57ba941b6dfeacbd))


### BREAKING CHANGES

* **http-routing:** The FileRouteCache class has been removed because
it's duplicated functionality with snicco/kernel.
If you use snicco/http-routing-bundle, routes
will still be cached to a file.
Otherwise, you can use the new CallbackRouteCache.php
or implement your own RouteCache.
* **kernel:** The ConfigCache.php interface
has been renamed to BootstrapCache.php
to better reflect its usage and
has also been marked as @internal

# [2.0.0-beta.4](https://github.com/snicco/snicco/compare/v2.0.0-beta.3...v2.0.0-beta.4) (2024-09-02)


### Features

* stricter directory permissions for all cache files ([83be859](https://github.com/snicco/snicco/commit/83be8599604e046b06862a77686e95c98faf2af9))


### BREAKING CHANGES

* all cache directories are created with 0700
permissions and all files with 0600.
Previously, they were created with 0755/0644.

# [2.0.0-beta.3](https://github.com/snicco/snicco/compare/v2.0.0-beta.2...v2.0.0-beta.3) (2024-09-01)


### Features

* **kernel:** try to create non-existing cache dirs ([a5cba06](https://github.com/snicco/snicco/commit/a5cba0681d93f93993046c5e93898b86102b8afc))

# [2.0.0-beta.2](https://github.com/snicco/snicco/compare/v2.0.0-beta.1...v2.0.0-beta.2) (2024-09-01)


### Features

* **http-routing,http-routing-bundle:** try to create non-existing cache dirs ([1ff6fac](https://github.com/snicco/snicco/commit/1ff6fac2cd25119d58b30483181598c4c892be66))

# [2.0.0-beta.1](https://github.com/snicco/snicco/compare/v1.10.0...v2.0.0-beta.1) (2024-09-01)


### Bug Fixes

* **kernel:** don't check if directories are readable in constructor ([4d136d7](https://github.com/snicco/snicco/commit/4d136d75f82b09195709644ed35a17158edff334))


### BREAKING CHANGES

* **kernel:** Previously creating an instance of the kernel class always asserted
that cache/log/config dirs are
readable.
This created a lot of development complexities as these directories
always had to be pre-created before a kernel instance could be created.
It also creates runtime overhead for little additional safety.
There is no way to ensure that a directory did not go missing between
Directories::__construct(), and the usage of any directory.
Callers of the Directory class are now responsible for error handling,
as they should always have.

# [1.10.0](https://github.com/snicco/snicco/compare/v1.9.1...v1.10.0) (2024-05-08)


### Features

* **testing-bundle:** allow specifying a default kernel env for all tests ([97f7ed0](https://github.com/snicco/snicco/commit/97f7ed0b5d036a1be28c8933e5fd3ab84ed35bc7))

## [1.9.1](https://github.com/snicco/snicco/compare/v1.9.0...v1.9.1) (2024-04-27)


### Bug Fixes

* **better-wp-cli-testing:** use temp stream instead of memory as test stdin ([4411702](https://github.com/snicco/snicco/commit/4411702b3266a22d3457e42d8065395c0673f345))
* **better-wp-hooks:** fix edge-case on wp6.4 and php_int_min ([b640af1](https://github.com/snicco/snicco/commit/b640af11743a3b24e5664b0c7f046eef5742a9ad))
* **better-wpdb:** make read locks compatible with mariadb ([cdf9335](https://github.com/snicco/snicco/commit/cdf93358b85640b270287262e8914fb3e451e731)), closes [#192](https://github.com/snicco/snicco/issues/192)

# [1.9.0](https://github.com/snicco/snicco/compare/v1.8.1...v1.9.0) (2023-09-20)


### Bug Fixes

* **http-routing-bundle:** don't warn for .ini output buffering ([7591992](https://github.com/snicco/snicco/commit/75919924e12a070668cfcb3f3be268b1b51a8e59)), closes [#188](https://github.com/snicco/snicco/issues/188)


### Features

* **http-routing-bundle:** allow customizing admin hooks ([6682c7f](https://github.com/snicco/snicco/commit/6682c7fe10cec1ee05bd6668765805b82c4f5745))

## [1.8.1](https://github.com/snicco/snicco/compare/v1.8.0...v1.8.1) (2023-08-13)


### Bug Fixes

* **http-routing,http-routing-bundle:** fix http/https port parsing from url ([2de9973](https://github.com/snicco/snicco/commit/2de9973337db6de7440714b123e07e45cdc30555))

# [1.8.0](https://github.com/snicco/snicco/compare/v1.7.0...v1.8.0) (2023-08-13)


### Features

* **http-routing-bundle:** add support for relative route directories ([7d54bd2](https://github.com/snicco/snicco/commit/7d54bd26e00c1aa357668319a1c02cb7e92e09a2))
* **http-routing,http-routing-bundle:** allow login path to be dynamic for url generation ([8de8c9e](https://github.com/snicco/snicco/commit/8de8c9e4a739663bb0ac941b7205d5f4c17237e0))
* **http-routing,http-routing-bundle:** allow url generation context from runtime values ([6411cd5](https://github.com/snicco/snicco/commit/6411cd5c1e9854ed17dae625d01bc32cd7e42084))
* **kernel:** add get_xxx_or_null methods to config classes ([b4c1f2a](https://github.com/snicco/snicco/commit/b4c1f2afbfbeb7ed7d412fa6f2a6cf72403f26f4))
* **templating-bundle:** allow relative directories in configuration ([a1db921](https://github.com/snicco/snicco/commit/a1db921132e39916d16e9abf8e1b1463c07e8c35))

# [1.7.0](https://github.com/snicco/snicco/compare/v1.6.2...v1.7.0) (2023-08-12)


### Bug Fixes

* **better-wp-mail:** don't reset global phpmailer if not set ([2a263ec](https://github.com/snicco/snicco/commit/2a263ec4b1f3b629a40828ac6b2aa51c553f6344))


### Features

* **http-routing-bundle:** allow any class/interface in log-level map ([41d0f8d](https://github.com/snicco/snicco/commit/41d0f8dd51d364b05231c942307e36376e8c4808)), closes [#181](https://github.com/snicco/snicco/issues/181)
* **http-routing-bundle:** log warnings if output buffering is enabled ([64fdc40](https://github.com/snicco/snicco/commit/64fdc40bac10c678f6d19c4cf569c643204f672f))

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
