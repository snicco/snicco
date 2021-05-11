<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use PHPUnit\Framework\Assert;
	use Psr\Log\AbstractLogger;
	use WPEmerge\Support\Arr;

	class TestLogger extends AbstractLogger {

		private $records = [];

		private $messages = [];

		private $context = [];

		public function log( $level, $message, array $context = [] ) {

			$this->records[ $level ] = [ 'message' => $message, 'context' => $context ];

			$this->messages[] = $message;
			$this->context[]  = $context;

		}

		public function assertHasLogEntry( $message, array $context = [] ) {

			Assert::assertContains( $message, $this->messages );

			if ( $context !== [] ) {

				Assert::assertContains( $context, $this->context );

			}

		}

		public function assertHasNoLogEntries( string $level = null ) {

			if ( ! $level ) {

				Assert::assertSame( [], $this->records );

				return;
			}

			Assert::assertEmpty( $this->records[ $level ] );

		}

		public function assertHasLogLevelEntry( string $level, $message, array $context = [] ) {

			$record = Arr::flattenOnePreserveKeys( $this->records[ $level ] );

			Assert::assertSame( $message, $record['message'] );

			if ( $context !== [] ) {

				Assert::assertSame( $context, $record['context'] );

			}

			}


		}