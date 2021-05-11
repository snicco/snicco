<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Models;

	use Illuminate\Database\Eloquent\Model as EloquentModel;

	class Country extends EloquentModel {


		public function teams() {

			return $this->hasMany(Team::class);

		}


	}