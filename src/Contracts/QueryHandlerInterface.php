<?php

declare(strict_types=1);

namespace Momo\Bus\Contracts;

interface QueryHandlerInterface
{
    public function handle(QueryInterface $query): mixed;
}
