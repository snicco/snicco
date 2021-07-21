<?php


    declare(strict_types = 1);


    namespace Snicco\Database\Models;

    use Illuminate\Database\Eloquent\Model as Eloquent;

    class Post extends Eloquent
    {

        protected $primaryKey = 'ID';
        const CREATED_AT = 'post_date';
        const UPDATED_AT = 'post_modified';

    }