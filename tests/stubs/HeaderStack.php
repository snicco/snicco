<?php

    /**
     * This is a direct copy of zend-diactoros/test/TestAsset/Functions.php and is used to override
     * header() and headers_sent() so we can test that they do the right thing.
     *
     */

    declare(strict_types=1);

    namespace Tests\stubs;

    use PHPUnit\Framework\Assert;
    use WPEmerge\Support\Str;

    use function explode;
    use function trim;

    /**
     * Zend Framework (http://framework.zend.com/)
     *
     * This file exists to allow overriding the various output-related functions
     * in order to test what happens during the `Server::listen()` cycle.
     *
     * These functions include:
     *
     * - headers_sent(): we want to always return false so that headers will be
     *   emitted, and we can test to see their values.
     * - header(): we want to aggregate calls to this function.
     *
     * The HeaderStack class then aggregates that information for us, and the test
     * harness resets the values pre and post test.
     *
     * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
     * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
     */

    /**
     * Store output artifacts
     */
    class HeaderStack
    {
        /**
         * @var string[][]
         */
        private static $data = [];

        /**
         * Reset state
         */
        public static function reset()
        {
            self::$data = [];
        }

        /**
         * Push a header on the stack
         *
         * @param array $header
         */
        public static function push(array $header)
        {
            self::$data[] = $header;
        }

        /**
         * Return the current header stack
         *
         * @return string[][]
         */
        public static function stack() : array
        {
            return self::$data;
        }

        /**
         * Verify if there's a header line on the stack
         *
         * @param  string  $header
         * @param  string|null  $value
         *
         */
        public static function assertHas(string $header, string $value = null)
        {

            $header_found = false;

            foreach (self::$data as $item) {

                $components = explode(':', $item['header']);

                if (trim($components[0]) === $header) {

                    if ( $value ) {

                        Assert::assertStringContainsString(
                            $value,
                            $actual = Str::after($item['header'], ':'),
                            "The value for header {$header} is {$actual}. Expected: {$value}"
                        );

                    }

                    $header_found = true;
                    break;

                }


            }

            Assert::assertTrue($header_found, "Header {$header} was expected but not found.");



        }

        public static function isEmpty() : bool
        {
            return self::$data === [];
        }

        public static function assertHasStatusCode(int $code)
        {
            if ( ! isset(self::$data[0]['status_code'] ) ) {

                Assert::fail('Status code header not found');

            }

            Assert::assertSame(
                $actual = self::$data[0]['status_code'],
                $code,
                "Actual status code: {$actual}. Expected: {$code}");

        }

    }