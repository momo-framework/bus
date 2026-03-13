<?php

declare(strict_types=1);

namespace Contracts;

interface QueryHandlerInterface
{
    public function handle(QueryInterface $query): mixed;
}
