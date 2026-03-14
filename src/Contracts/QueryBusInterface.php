<?php

declare(strict_types=1);

namespace Momo\Bus\Contracts;

interface QueryBusInterface
{
    public function ask(QueryInterface $query): mixed;

    /**
     * @param class-string<QueryInterface> $queryClass
     */
    public function register(string $queryClass, QueryHandlerInterface $handler): void;
}
