<?php


    declare(strict_types = 1);


    namespace WPEmerge\Traits;

    use Closure;
    use Opis\Closure\SerializableClosure;

    trait PreparesRouteForExport
    {

        private function serializeAction($action)
        {

            if ($action instanceof Closure && class_exists(SerializableClosure::class)) {

                $closure = new SerializableClosure($action);

                $action = \Opis\Closure\serialize($closure);

            }

            return $action;

        }

        private function prepareForVarExport(array $asArray) : array
        {

            $asArray['action'] = $this->serializeAction($asArray['action']);

            return $asArray;

        }

    }