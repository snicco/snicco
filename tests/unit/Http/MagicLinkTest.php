<?php


    declare(strict_types = 1);


    namespace Tests\unit\Http;

    use Carbon\Carbon;
    use Snicco\Testing\TestDoubles\TestMagicLink;
    use Tests\stubs\TestRequest;
    use Tests\unit\Routing\UrlGeneratorTest;

    /**
     * NOTE: The validation test for the MagicLink class are already implemented
     * inside @see UrlGeneratorTest
     */
    class MagicLinkTest extends \PHPUnit\Framework\TestCase
    {

        /**
         * @var \Snicco\Testing\TestDoubles\TestMagicLink
         */
        private $magic_link;

        /**
         * @var TestRequest
         */
        private $request;

        /** @var Carbon */
        private $expires;

        protected function setUp() :void
        {

            $this->magic_link = new TestMagicLink();
            $this->magic_link->setLottery([0,100]);
            $this->request = TestRequest::from('GET', 'foo');
            $this->expires = Carbon::now();

            parent::setUp();

        }

        /** @test */
        public function a_magic_link_is_stored () {

            $expires_at = $this->expires->getTimestamp();
            $signature = $this->magic_link->create('/foo', $expires_at, $this->request );

            $links = $this->magic_link->getStored();

            $this->assertArrayHasKey($signature, $links);
            $this->assertSame($expires_at, $links[$signature]);

        }

        /** @test */
        public function garbage_collection_works () {

            $expires_at = $this->expires->getTimestamp();

            $foo_signature = $this->magic_link->create('/foo', $expires_at, $this->request );
            $links = $this->magic_link->getStored();
            $this->assertArrayHasKey($foo_signature, $links);
            $this->assertSame($expires_at, $links[$foo_signature]);

            Carbon::setTestNow($this->expires->addSeconds(10));
            $this->magic_link->setLottery([100, 100]);

            $bar_signature = $this->magic_link->create('/bar', $expires_at, $this->request );
            $links = $this->magic_link->getStored();
            $this->assertArrayHasKey($bar_signature, $links);
            $this->assertSame($expires_at, $links[$bar_signature]);

            $this->assertArrayNotHasKey($foo_signature, $links, 'garbage collection did not work.');

            Carbon::setTestNow();

        }

        /** @test */
        public function invalidating_a_magic_links_destroys_it_from_storage () {

            $expires_at = $this->expires->getTimestamp();
            $signature = $this->magic_link->create('/foo', $expires_at, $this->request );

            $this->magic_link->invalidate('/foo?signature='.$signature);

            $this->assertArrayNotHasKey($signature, $this->magic_link->getStored());


        }

        /** @test */
        public function a_magic_link_is_only_valid_if_it_exists_in_the_persistent_storage () {

            $expires_at = $this->expires->getTimestamp();
            $signature = $this->magic_link->create("/foo?expires={$expires_at}", $expires_at, $this->request );

            $request = TestRequest::fromFullUrl('GET', "https://foo.com/foo?expires={$expires_at}&signature={$signature}");

            $this->assertTrue($this->magic_link->hasValidSignature($request));

            $this->magic_link->invalidate("/foo?signature={$signature}");

            $this->assertFalse($this->magic_link->hasValidSignature($request));


        }

    }
