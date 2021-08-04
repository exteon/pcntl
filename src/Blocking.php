<?php

    namespace Exteon\Pcntl;

    use ErrorException;
    use Exteon\Pcntl\Exception\BlockingTimeoutException;
    use InvalidArgumentException;

    abstract class Blocking
    {
        private static $isInWithTimeout = false;

        /**
         * Adds a timeout for blocking lock operations such as flock, dba_open
         * Gotcha: replaces any SIGALRM handlers already defined
         *
         * @throws BlockingTimeoutException
         * @throws ErrorException
         */
        public static function withTimeout(int $seconds, callable $callable)
        {
            // Make sure we are non-reentrant
            if (self::$isInWithTimeout) {
                throw new ErrorException(__FUNCTION__ . ' is non-reentrant');
            }
            if ($seconds <= 0) {
                throw new InvalidArgumentException('Timeout needs to be >0');
            }
            self::$isInWithTimeout = true;
            $pcntl_async_signals = pcntl_async_signals();
            pcntl_async_signals(true);
            $tripped = false;
            pcntl_signal(
                SIGALRM,
                function () use (&$tripped) {
                    $tripped = true;
                }
            );
            pcntl_alarm($seconds);
            try {
                $result = $callable();
            } finally {
                $throw = false;
                if (!$tripped) {
                    pcntl_alarm(0);
                } else {
                    $throw = true;
                }
                pcntl_signal(SIGALRM, SIG_DFL);
                pcntl_async_signals($pcntl_async_signals);
                self::$isInWithTimeout = false;
            }
            if ($throw) {
                throw new BlockingTimeoutException();
            }
            return $result;
        }
    }