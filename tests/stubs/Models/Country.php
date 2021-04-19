<?php


	namespace Tests\stubs\Models;

	use Illuminate\Database\Eloquent\Model as EloquentModel;

	class Country extends EloquentModel {


		public function teams() {

			return $this->hasMany(Team::class);

		}


	}