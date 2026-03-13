<?php

declare(strict_types=1);

namespace Momo\Bus\Tests\Unit;

use Contracts\CommandHandlerInterface;
use Contracts\CommandInterface;
use Momo\Bus\SynchronousCommandBus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(SynchronousCommandBus::class)]
final class SynchronousCommandBusTest extends TestCase
{
    private SynchronousCommandBus $bus;

    protected function setUp(): void
    {
        $this->bus = new SynchronousCommandBus();
    }

    #[Test]
    public function dispatch_calls_registered_handler(): void
    {
        $command = $this->makeCommand();
        $handler = $this->makeCommandHandler();

        $this->bus->register($command::class, $handler);
        $this->bus->dispatch($command);

        self::assertTrue($handler->called);
    }

    #[Test]
    public function dispatch_passes_exact_command_instance_to_handler(): void
    {
        $command = $this->makeCommand();
        $handler = $this->makeCommandHandler();

        $this->bus->register($command::class, $handler);
        $this->bus->dispatch($command);

        self::assertSame($command, $handler->received);
    }

    #[Test]
    public function dispatch_throws_when_no_handler_registered(): void
    {
        $command = $this->makeCommand();

        $this->expectException(RuntimeException::class);
        $this->bus->dispatch($command);
    }

    #[Test]
    public function dispatch_exception_message_contains_command_class_name(): void
    {
        $command = $this->makeCommand();

        try {
            $this->bus->dispatch($command);
            self::fail('RuntimeException expected');
        } catch (RuntimeException $runtimeException) {
            self::assertStringContainsString($command::class, $runtimeException->getMessage());
        }
    }

    #[Test]
    public function dispatch_calls_handler_exactly_once(): void
    {
        $command = $this->makeCommand();
        $handler = $this->makeCommandHandler();

        $this->bus->register($command::class, $handler);
        $this->bus->dispatch($command);

        self::assertSame(1, $handler->callCount);
    }

    #[Test]
    public function dispatch_calls_correct_handler_for_each_command_type(): void
    {
        $commandA = new class implements CommandInterface {};
        $commandB = new class implements CommandInterface {};

        $handlerA = $this->makeCommandHandler();
        $handlerB = $this->makeCommandHandler();

        $this->bus->register($commandA::class, $handlerA);
        $this->bus->register($commandB::class, $handlerB);

        $this->bus->dispatch($commandA);

        self::assertTrue($handlerA->called);
        self::assertFalse($handlerB->called);
    }

    #[Test]
    public function dispatch_can_handle_multiple_commands_of_different_types(): void
    {
        $commandA = new class implements CommandInterface {};
        $commandB = new class implements CommandInterface {};

        $handlerA = $this->makeCommandHandler();
        $handlerB = $this->makeCommandHandler();

        $this->bus->register($commandA::class, $handlerA);
        $this->bus->register($commandB::class, $handlerB);

        $this->bus->dispatch($commandA);
        $this->bus->dispatch($commandB);

        self::assertTrue($handlerA->called);
        self::assertTrue($handlerB->called);
    }

    #[Test]
    public function dispatch_does_not_return_value(): void
    {
        $command = $this->makeCommand();
        $handler = $this->makeCommandHandler();

        $this->bus->register($command::class, $handler);
        $result = $this->bus->dispatch($command);

        self::assertNull($result);
    }

    #[Test]
    public function register_overwrites_previous_handler_for_same_command(): void
    {
        $command = $this->makeCommand();

        $handlerA = $this->makeCommandHandler();
        $handlerB = $this->makeCommandHandler();

        $this->bus->register($command::class, $handlerA);
        $this->bus->register($command::class, $handlerB);

        $this->bus->dispatch($command);

        self::assertFalse($handlerA->called);
        self::assertTrue($handlerB->called);
    }

    #[Test]
    public function register_same_handler_for_different_commands(): void
    {
        $commandA = $this->makeCommand();
        $commandB = $this->makeCommand();
        $handler  = $this->makeCommandHandler();

        $this->bus->register($commandA::class, $handler);
        $this->bus->register($commandB::class, $handler);

        $this->bus->dispatch($commandA);
        $this->bus->dispatch($commandB);

        self::assertSame(2, $handler->callCount);
    }

    private function makeCommand(): CommandInterface
    {
        return new class implements CommandInterface {};
    }

    private function makeCommandHandler(): object
    {
        return new class implements CommandHandlerInterface {
            public bool $called    = false;

            public int  $callCount = 0;

            public mixed $received = null;

            public function handle(CommandInterface $command): void
            {
                $this->called = true;
                $this->callCount++;
                $this->received = $command;
            }
        };
    }
}
