<?php

    namespace Test\Exteon\Pcntl;

    use ErrorException;
    use Exteon\Pcntl\Blocking;
    use Exteon\Pcntl\Exception\BlockingTimeoutException;
    use InvalidArgumentException;
    use PHPUnit\Framework\TestCase;

    class BlockingTest extends TestCase
    {
        public function testWithTimeout(): void
        {
            $filename = tempnam('/tmp', 'test-exteon-pcntl');
            file_put_contents($filename, '');
            $h1 = fopen($filename, 'w');
            flock($h1, LOCK_EX);
            $exceptionThrown = false;
            $startTime = microtime(true);
            try {
                Blocking::withTimeout(
                    2,
                    function () use ($filename) {
                        $h2 = fopen($filename, 'w');
                        flock($h2, LOCK_EX);
                    }
                );
            } catch (BlockingTimeoutException $e) {
                $exceptionThrown = true;
            }
            $elapsed = microtime(true) - $startTime;
            self::assertTrue($exceptionThrown);
            self::assertGreaterThan(2, $elapsed);
            self::assertLessThan(3, $elapsed);
        }

        /**
         * @throws BlockingTimeoutException
         */
        public function testReentrance(): void
        {
            $this->expectException(ErrorException::class);
            Blocking::withTimeout(1, function () {
                Blocking::withTimeout(1, function () {
                });
            });
        }

        /**
         * @throws BlockingTimeoutException
         */
        public function testNegativeTimeout(): void {
            $this->expectException(InvalidArgumentException::class);
            Blocking::withTimeout(-1, function () {
            });
        }

        /**
         * @throws BlockingTimeoutException
         */
        public function testZeroTimeout(): void {
            $this->expectException(InvalidArgumentException::class);
            Blocking::withTimeout(0, function () {
            });
        }
    }
