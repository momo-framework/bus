<?php

/**
 * Part of Momo Framework.
 *
 * @copyright Vahe Sargsyan <w33bvGL>
 * @license   AGPL-3.0-or-later <https://www.gnu.org/licenses/agpl-3.0.html>
 * @link      https://github.com/momo-framework
 */

declare(strict_types=1);

namespace Momo\Bus\Tests\Unit;

use Momo\Bus\Contracts\QueryHandlerInterface;
use Momo\Bus\Contracts\QueryInterface;
use Momo\Bus\SynchronousQueryBus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * @internal
 */
#[CoversClass(SynchronousQueryBus::class)]
final class SynchronousQueryBusTest extends TestCase
{
    private SynchronousQueryBus $bus;

    protected function setUp(): void
    {
        $this->bus = new SynchronousQueryBus();
    }

    #[Test]
    public function ask_returns_value_from_handler(): void
    {
        $query = $this->makeQuery();
        $handler = $this->makeQueryHandler(static fn (): array => ['id' => 1, 'name' => 'test']);

        $this->bus->register($query::class, $handler);

        self::assertSame(['id' => 1, 'name' => 'test'], $this->bus->ask($query));
    }

    #[Test]
    public function ask_returns_null_when_handler_returns_null(): void
    {
        $query = $this->makeQuery();
        $handler = $this->makeQueryHandler(static fn (): null => null);

        $this->bus->register($query::class, $handler);

        self::assertNull($this->bus->ask($query));
    }

    #[Test]
    public function ask_returns_scalar_value(): void
    {
        $query = $this->makeQuery();
        $handler = $this->makeQueryHandler(static fn (): int => 42);

        $this->bus->register($query::class, $handler);

        self::assertSame(42, $this->bus->ask($query));
    }

    #[Test]
    public function ask_returns_object_value(): void
    {
        $query = $this->makeQuery();
        $expected = new stdClass();
        $handler = $this->makeQueryHandler(static fn (): stdClass => $expected);

        $this->bus->register($query::class, $handler);

        self::assertSame($expected, $this->bus->ask($query));
    }

    #[Test]
    public function ask_passes_exact_query_instance_to_handler(): void
    {
        $query = $this->makeQuery();
        $received = null;

        $handler = $this->makeQueryHandler(static function (QueryInterface $q) use (&$received): null {
            $received = $q;

            return null;
        });

        $this->bus->register($query::class, $handler);
        $this->bus->ask($query);

        self::assertSame($query, $received);
    }

    #[Test]
    public function ask_throws_when_no_handler_registered(): void
    {
        $query = $this->makeQuery();

        $this->expectException(RuntimeException::class);
        $this->bus->ask($query);
    }

    #[Test]
    public function ask_exception_message_contains_query_class_name(): void
    {
        $query = $this->makeQuery();

        try {
            $this->bus->ask($query);
            self::fail('RuntimeException expected');
        } catch (RuntimeException $runtimeException) {
            self::assertStringContainsString($query::class, $runtimeException->getMessage());
        }
    }

    #[Test]
    public function ask_calls_handler_exactly_once(): void
    {
        $callCount = 0;
        $query = $this->makeQuery();
        $handler = $this->makeQueryHandler(static function () use (&$callCount): null {
            $callCount++;

            return null;
        });

        $this->bus->register($query::class, $handler);
        $this->bus->ask($query);

        self::assertSame(1, $callCount);
    }

    #[Test]
    public function ask_routes_each_query_type_to_correct_handler(): void
    {
        $queryA = new class implements QueryInterface {};
        $queryB = new class implements QueryInterface {};

        $handlerA = $this->makeQueryHandler(static fn (): string => 'result-a');
        $handlerB = $this->makeQueryHandler(static fn (): string => 'result-b');

        $this->bus->register($queryA::class, $handlerA);
        $this->bus->register($queryB::class, $handlerB);

        self::assertSame('result-a', $this->bus->ask($queryA));
        self::assertSame('result-b', $this->bus->ask($queryB));
    }

    #[Test]
    public function ask_does_not_call_wrong_handler(): void
    {
        $queryA = new class implements QueryInterface {};
        $queryB = new class implements QueryInterface {};

        $called = false;
        $handlerA = $this->makeQueryHandler(static fn (): null => null);
        $handlerB = $this->makeQueryHandler(static function () use (&$called): null {
            $called = true;

            return null;
        });

        $this->bus->register($queryA::class, $handlerA);
        $this->bus->register($queryB::class, $handlerB);

        $this->bus->ask($queryA);

        self::assertFalse($called);
    }

    #[Test]
    public function register_overwrites_previous_handler_for_same_query(): void
    {
        $query = $this->makeQuery();
        $handlerA = $this->makeQueryHandler(static fn (): string => 'first');
        $handlerB = $this->makeQueryHandler(static fn (): string => 'second');

        $this->bus->register($query::class, $handlerA);
        $this->bus->register($query::class, $handlerB);

        self::assertSame('second', $this->bus->ask($query));
    }

    #[Test]
    public function register_same_handler_instance_for_multiple_queries(): void
    {
        $queryA = $this->makeQuery();
        $queryB = $this->makeQuery();
        $handler = $this->makeQueryHandler(static fn (): string => 'ok');

        $this->bus->register($queryA::class, $handler);
        $this->bus->register($queryB::class, $handler);

        self::assertSame('ok', $this->bus->ask($queryA));
        self::assertSame('ok', $this->bus->ask($queryB));
    }

    private function makeQuery(): QueryInterface
    {
        return new class implements QueryInterface {};
    }

    /**
     * @param callable(QueryInterface): mixed $returnFn
     */
    private function makeQueryHandler(callable $returnFn): QueryHandlerInterface
    {
        return new class($returnFn) implements QueryHandlerInterface {
            /** @var callable(QueryInterface): mixed */
            private $fn;

            /** @param callable(QueryInterface): mixed $fn */
            public function __construct(callable $fn)
            {
                $this->fn = $fn;
            }

            public function handle(QueryInterface $query): mixed
            {
                return ($this->fn)($query);
            }
        };
    }
}
