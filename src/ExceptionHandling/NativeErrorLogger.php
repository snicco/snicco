<?php


    namespace Snicco\ExceptionHandling;

    use Psr\Log\AbstractLogger;

    class NativeErrorLogger extends AbstractLogger
    {

        // This will log to default log file which is configured by WordPress and is by default
        // placed inside /wp-content.
        public function log($level, $message, array $context = [])
        {

            $message = strval($message);
            error_log("[$level]: $message");
        }

    }