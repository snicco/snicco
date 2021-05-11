<?php


	declare( strict_types = 1 );


	namespace Tests\unit\Middleware;

	use WP_Post;

	trait WordpressFixtures {

		private function newAdmin( )  {

			return self::factory()->user->create( [
				'role' => 'administrator',
			]);


		}

		private function newAuthor()  {

			return self::factory()->user->create( [
				'role' => 'author',
			]);


		}

		private function login(int $id ) {

			wp_set_current_user($id);

		}

		private function logout( $user ) {

			wp_logout();

		}

		private function newPost ( int $author_id  ) : WP_Post{

			return $this->factory()->post->create_and_get([
				'post_author' => $author_id
			]);

		}

	}