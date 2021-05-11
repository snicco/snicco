<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Models;

	use Illuminate\Database\Eloquent\Model as EloquentModel;

	class Team extends EloquentModel {



		public function country() {

			return $this->belongsTo(Country::class);

		}


	}