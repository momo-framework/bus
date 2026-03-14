<?php

declare(strict_types=1);

namespace Momo\Bus;

use Momo\Bus\Contracts\QueryBusInterface;
use Momo\Bus\Contracts\QueryHandlerInterface;
use Momo\Bus\Contracts\QueryInterface;

final class SynchronousQueryBus implements QueryBusInterface
{
    /** @var array<class-string<QueryInterface>, QueryHandlerInterface> */
    private array $handlers = [];

    public function ask(QueryInterface $query): mixed
    {
        $class = $query::class;

        if (!isset($this->handlers[$class])) {
            throw new \RuntimeException('No handler registered for query: ' . $class);
        }

        return $this->handlers[$class]->handle($query);
    }

    public function register(string $queryClass, QueryHandlerInterface $handler): void
    {
        $this->handlers[$queryClass] = $handler;
    }
}
