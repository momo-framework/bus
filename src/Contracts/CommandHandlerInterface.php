<?php

declare(strict_types=1);

namespace Contracts;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): void;
}
