<?php


	namespace WPEmerge;

	use BetterWpdb\WpConnection;
	use WPEmerge\Contracts\RouteModelResolver;

	class WpdbRouteModelResolver implements RouteModelResolver {

		/**
		 * @var \BetterWpdb\WpConnection
		 */
		private $connection;

		public function __construct(WpConnection $connection) {

			$this->connection = $connection;

		}

		public function fetchModel ( $value, $model, $column = 'id') {

			$model::findOrFail([$column => $value ]);

			$foo = 'bar';

		}

	}