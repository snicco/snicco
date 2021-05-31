<?php


    declare(strict_types = 1);


    namespace WPEmerge\Traits;

    use Closure;
    use Opis\Closure\SerializableClosure;
    use WPEmerge\Routing\Conditions\CustomCondition;

    trait PreparesRouteForExport
    {

        private function serializeAttribute($action)
        {

            if ($action instanceof Closure && class_exists(SerializableClosure::class)) {

                $closure = new SerializableClosure($action);

                $action = \Opis\Closure\serialize($closure);

            }

            return $action;

        }

        private function prepareForVarExport(array $asArray) : array
        {

            $asArray['action'] = $this->serializeAttribute($asArray['action']);

            $asArray['wp_query_filter'] = $this->serializeAttribute($asArray['wp_query_filter']);

            $asArray['conditions'] = collect($asArray['conditions'])
                ->map(function (array $condition) {

                    return $this->serializeCustomConditions($condition);

                })->all();

            return $asArray;

        }

        private function serializeCustomConditions(array $condition_blueprint) {

            $condition = $condition_blueprint['instance'];

            if ( ! is_object($condition) ) {
                return $condition_blueprint;

            }


             if ( ! $condition instanceof CustomCondition) {
                return $condition_blueprint;
             }


             $serializable = clone $condition;

             $serializable->setCallable(
                 $this->serializeAttribute($condition->getCallable())
             );

            $condition_blueprint['instance'] = serialize($serializable);

            return $condition_blueprint;

        }

    }