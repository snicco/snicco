<?php


    declare(strict_types = 1);


    namespace Tests\unit\Http;

    use Carbon\Carbon;
    use DateTime;
    use Snicco\Http\Cookie;
    use PHPUnit\Framework\TestCase;

    class CookieTest extends TestCase
    {

        public function testDefault () {

            $cookie = new Cookie('foo', 'bar');

            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => null,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ], $cookie->properties());

        }

        public function testSetProperties () {

            $cookie = new Cookie('foo', 'bar');

            $cookie->setProperties([
                'secure' => false,
                'httponly' => false,
                'samesite' => 'Strict',
            ]);

            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => null,
                'secure' => false,
                'httponly' => false,
                'samesite' => 'Strict',
            ], $cookie->properties());

        }

        public function testAllowJs () {

            $cookie = new Cookie('foo', 'bar');
            $cookie->allowJs();

            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => null,
                'secure' => true,
                'httponly' => false,
                'samesite' => 'Lax',
            ], $cookie->properties());

        }

        public function testAllowUnsecure () {


            $cookie = new Cookie('foo', 'bar');
            $cookie->allowUnsecure();

            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => null,
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ], $cookie->properties());


        }

        public function testSameSite () {

            $cookie = new Cookie('foo', 'bar');
            $cookie->sameSite('strict');
            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => null,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ], $cookie->properties());

            $cookie->sameSite('lax');
            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => null,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ], $cookie->properties());

            $cookie->sameSite('none');
            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => null,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None',
            ], $cookie->properties());

            $this->expectException(\LogicException::class);

            $cookie->sameSite('bogus');

        }

        public function testExpiresInteger () {

            $cookie = new Cookie('foo', 'bar');
            $cookie->expires(1000);
            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => 1000,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ], $cookie->properties());

        }

        public function testExpiresDatetimeInterface () {

            $cookie = new Cookie('foo', 'bar');

            $date = new DateTime('2000-01-01');

            $cookie->expires($date);

            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => $date->getTimestamp(),
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ], $cookie->properties());

        }

        public function testExpiresCarbon () {

            $cookie = new Cookie('foo', 'bar');

            $date = Carbon::createFromDate('2000', '01', '01');

            $cookie->expires($date);

            $this->assertSame([
                'value' => 'bar',
                'domain' => null,
                'hostonly' => true,
                'path' => '/',
                'expires' => $date->getTimestamp(),
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ], $cookie->properties());

        }

        public function testExpiresInvalidArgumentThrowsException () {

            $this->expectException(\InvalidArgumentException::class);
            $cookie = new Cookie('foo', 'bar');
            $cookie->expires('1000');
        }

    }
