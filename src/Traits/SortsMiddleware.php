<?php


    declare(strict_types = 1);


    namespace WPMvc\Traits;

    trait SortsMiddleware
    {

        /**
         * Get priority for a specific middleware.
         * This is in reverse compared to definition order.
         * Middleware with unspecified priority will yield -1.
         *
         * @param  string|array  $middleware
         * @param  array  $priority_map
         *
         * @return integer
         */
        private function getMiddlewarePriorityForMiddleware( $middleware, array $priority_map ) : int {

            if ( is_array( $middleware ) ) {
                $middleware = $middleware[0];
            }

            $increasing_priority = array_reverse( $priority_map );
            $priority            = array_search( $middleware, $increasing_priority );

            return $priority !== false ? (int) $priority : - 1;
        }

        /**
         * Sort array of fully qualified middleware class names by priority in ascending order.
         *
         * @param  string[]  $middleware
         * @param  array  $priority_map
         *
         * @return array
         */
        private function sortMiddleware( array $middleware, array $priority_map ) : array {

            $sorted = $middleware;

            usort( $sorted, function ( $a, $b ) use ( $middleware, $priority_map ) {

                $a_priority = $this->getMiddlewarePriorityForMiddleware( $a, $priority_map );
                $b_priority = $this->getMiddlewarePriorityForMiddleware( $b, $priority_map );
                $priority   = $b_priority - $a_priority;

                if ( $priority !== 0 ) {
                    return $priority;
                }

                // Keep relative order from original array.
                return array_search( $a, $middleware ) - array_search( $b, $middleware );
            } );

            return array_values( $sorted );
        }

    }