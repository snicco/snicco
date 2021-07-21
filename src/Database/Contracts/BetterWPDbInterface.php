<?php


    declare(strict_types = 1);


    namespace Snicco\Database\Contracts;

    use mysqli_result;

    interface BetterWPDbInterface
    {

        public function doSelect(string $sql, array $bindings) : array;

        public function doStatement(string $sql, array $bindings) : bool;

        public function doAffectingStatement($sql, array $bindings) : int;

        public function doUnprepared(string $sql) : bool;

        public function doCursorSelect(string $sql, array $bindings) : mysqli_result;

        public function startTransaction();

        public function commitTransaction();

        public function rollbackTransaction(  string $sql );

        public function createSavepoint( string $sql );

        public function lastInsertId() :int;

    }