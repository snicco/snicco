<?php


	declare( strict_types = 1 );
	//
//
// 	namespace Tests\unit\Responses;
//
// 	use Codeception\TestCase\WPTestCase;
// 	use Mockery;
// 	use Psr\Http\Message\ResponseInterface;
// 	use WPEmerge\Contracts\RequestInterface;
// 	use WPEmerge\Http\ResponseService;
// 	use WPEmerge\Contracts\ViewInterface;
// 	use WPEmerge\View\ViewService;
//
// 	/**
// 	 * @coversDefaultClass \WPEmerge\Http\ResponseService
// 	 */
// 	class ResponseServiceTest extends WPTestCase {
//
// 		public function setUp() : void {
//
// 			parent::setUp();
//
// 			$this->request      = Mockery::mock( RequestInterface::class );
// 			$this->view_service = Mockery::mock( ViewService::class );
// 			$this->subject      = new ResponseService( $this->request, $this->view_service );
// 		}
//
// 		public function tearDown() : void {
//
// 			parent::tearDown();
// 			Mockery::close();
//
// 			unset( $this->request );
// 			unset( $this->view_service );
// 			unset( $this->subject );
// 		}
//
// 		protected function readStream( $stream, $chunk_size = 4096 ) {
//
// 			$output = '';
// 			while ( ! $stream->eof() ) {
// 				$output .= $stream->read( $chunk_size );
// 			}
//
// 			return $output;
// 		}
//
// 		/**
// 		 * @covers ::response
// 		 */
// 		public function testResponse() {
//
// 			$this->assertInstanceOf( ResponseInterface::class, $this->subject->response() );
// 		}
//
// 		/**
// 		 * @covers ::output
// 		 */
// 		public function testOutut() {
//
// 			$expected = 'foobar';
//
// 			$subject = $this->subject->output( $expected );
// 			$this->assertEquals( $expected, $this->readStream( $subject->getBody() ) );
// 		}
//
// 		/**
// 		 * @covers ::json
// 		 */
// 		public function testJson() {
//
// 			$input    = [ 'foo' => 'bar' ];
// 			$expected = json_encode( $input );
//
// 			$subject = $this->subject->json( $input );
// 			$this->assertEquals( $expected, $this->readStream( $subject->getBody() ) );
// 		}
//
// 		/**
// 		 * @covers ::redirect
// 		 */
// 		public function testRedirect() {
//
// 			$this->request->shouldReceive( 'headers' )
// 			              ->with( 'Referer' )
// 			              ->andReturn( 'foo' );
//
// 			$this->assertEquals( 'foo', $this->subject->redirect()->back()
// 			                                          ->getHeaderLine( 'Location' ) );
//
// 			$request = Mockery::mock( RequestInterface::class );
//
// 			$request->shouldReceive( 'getHeaderLine' )
// 			        ->with( 'Referer' )
// 			        ->andReturn( 'bar' );
//
// 			$this->assertEquals( 'bar', $this->subject->redirect( $request )->back()
// 			                                          ->getHeaderLine( 'Location' ) );
// 		}
//
// 		/**
// 		 * @covers ::abort
// 		 */
// 		public function testError() {
//
// 			$expected1 = 404;
// 			$expected2 = 500;
//
// 			$view     = Mockery::mock( ViewInterface::class );
// 			$response = Mockery::mock( ResponseInterface::class );
//
// 			$this->view_service->shouldReceive( 'make' )
// 			                   ->with( [ $expected1, 'error', 'index' ] )
// 			                   ->andReturn( $view )
// 			                   ->once()
// 			                   ->ordered();
//
// 			$this->view_service->shouldReceive( 'make' )
// 			                   ->with( [ $expected2, 'error', 'index' ] )
// 			                   ->andReturn( $view )
// 			                   ->once()
// 			                   ->ordered();
//
// 			$view->shouldReceive( 'toResponse' )
// 			     ->andReturn( $response );
//
// 			$response->shouldReceive( 'withStatus' )
// 			         ->with( $expected1 )
// 			         ->andReturn( $response )
// 			         ->once()
// 			         ->ordered();
//
// 			$response->shouldReceive( 'withStatus' )
// 			         ->with( $expected2 )
// 			         ->andReturn( $response )
// 			         ->once()
// 			         ->ordered();
//
// 			$response1 = $this->subject->abort( $expected1 );
// 			$response2 = $this->subject->abort( $expected2 );
//
// 			$this->assertSame( $response, $response1 );
// 			$this->assertSame( $response, $response2 );
// 		}
//
// 		/**
// 		 * @covers ::abort
// 		 */
// 		public function testError_Admin_AdminTemplate() {
//
// 			$expected = 404;
//
// 			$view     = Mockery::mock( ViewInterface::class );
// 			$response = Mockery::mock( ResponseInterface::class );
//
// 			$this->view_service->shouldReceive( 'make' )
// 			                   ->with( [
// 				                   $expected . '-admin',
// 				                   'error-admin',
// 				                   'index-admin',
// 				                   $expected,
// 				                   'error',
// 				                   'index',
// 			                   ] )
// 			                   ->andReturn( $view )
// 			                   ->once();
//
// 			$view->shouldReceive( 'toResponse' )
// 			     ->andReturn( $response );
//
// 			$response->shouldReceive( 'withStatus' )
// 			         ->with( $expected )
// 			         ->andReturn( $response )
// 			         ->once();
//
// 			set_current_screen( 'options.php' );
//
// 			$this->assertSame( $response, $this->subject->abort( $expected ) );
//
// 			set_current_screen( 'front' );
// 		}
//
// 		/**
// 		 * @covers ::abort
// 		 */
// 		public function testError_Ajax_AjaxTemplate() {
//
// 			$expected = 404;
//
// 			$view     = Mockery::mock( ViewInterface::class );
// 			$response = Mockery::mock( ResponseInterface::class );
//
// 			$this->view_service->shouldReceive( 'make' )
// 			                   ->with( [
// 				                   $expected . '-ajax',
// 				                   'error-ajax',
// 				                   'index-ajax',
// 				                   $expected,
// 				                   'error',
// 				                   'index',
// 			                   ] )
// 			                   ->andReturn( $view )
// 			                   ->once();
//
// 			$view->shouldReceive( 'toResponse' )
// 			     ->andReturn( $response );
//
// 			$response->shouldReceive( 'withStatus' )
// 			         ->with( $expected )
// 			         ->andReturn( $response )
// 			         ->once();
//
// 			set_current_screen( 'options.php' );
// 			add_filter( 'wp_doing_ajax', '__return_true' );
//
// 			$this->assertSame( $response, $this->subject->abort( $expected ) );
//
// 			remove_filter( 'wp_doing_ajax', '__return_true' );
// 			set_current_screen( 'front' );
// 		}
//
// 	}
