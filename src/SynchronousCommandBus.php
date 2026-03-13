<?php

declare(strict_types=1);

namespace Momo\Bus;

use Contracts\CommandBusInterface;
use Contracts\CommandHandlerInterface;
use Contracts\CommandInterface;

final class SynchronousCommandBus implements CommandBusInterface
{
    /** @var array<class-string<CommandInterface>, CommandHandlerInterface> */
    private array $handlers = [];

    public function dispatch(CommandInterface $command): void
    {
        $class = $command::class;

        if (!isset($this->handlers[$class])) {
            throw new \RuntimeException('No handler registered for command: ' . $class);
        }

        $this->handlers[$class]->handle($command);
    }

    public function register(string $commandClass, CommandHandlerInterface $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }
}
