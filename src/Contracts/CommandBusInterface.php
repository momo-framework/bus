<?php

declare(strict_types=1);

namespace Contracts;

interface CommandBusInterface
{
    public function dispatch(CommandInterface $command): void;

    /**
     * @param class-string<CommandInterface> $commandClass
     */
    public function register(string $commandClass, CommandHandlerInterface $handler): void;
}
