<?php

declare(strict_types=1);

namespace Momo\Bus\Contracts;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): void;
}
