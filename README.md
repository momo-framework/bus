<div align="center">
  <img src="https://avatars.githubusercontent.com/u/255415480?s=200&v=4" alt="Momo Framework" width="120" height="120" />

  <h1>momo-framework/bus</h1>

  <p>
      Synchronous in-process CQRS buses for <a href="https://github.com/momo-framework">Momo Framework</a>. 
      Provides a strictly typed interface for Command and Query dispatching within a single request cycle.
  </p>    

  <p>
    <img src="https://github.com/momo-framework/bus/actions/workflows/ci.yml/badge.svg" alt="CI" />
    <img src="https://img.shields.io/packagist/v/momo-framework/bus.svg?style=flat" alt="Latest Version" />
    <img src="https://img.shields.io/packagist/dt/momo-framework/bus.svg?style=flat" alt="Total Downloads" />
    <img src="https://img.shields.io/badge/php-%3E%3D8.5-8892bf.svg" alt="PHP Version" />
    <img src="https://img.shields.io/badge/license-proprietary-red.svg" alt="License" />
    <img src="https://img.shields.io/badge/coverage-100%25-brightgreen.svg" alt="Coverage" />
    <img src="https://img.shields.io/badge/PHPStan-level%2010-brightblue.svg" alt="PHPStan" />
  </p>
</div>

---

## Overview

`momo-framework/bus` provides two lightweight, in-process buses for separating **write** and **read** operations in your application — following the [CQRS](https://martinfowler.com/bliki/CQRS.html) pattern.

| Bus          | Purpose                                   | Returns |
|--------------|-------------------------------------------|---------|
| `CommandBus` | Write operations — create, update, delete | `void`  |
| `QueryBus`   | Read operations — fetch, list, search     | `mixed` |

Both buses are **always synchronous** — handlers execute in the same process, in the same request cycle. For async processing, use [`momo-framework/queue`](https://github.com/momo-framework/queue).

Both buses enforce a **one-handler-per-message** contract. Registering a second handler for the same message class silently overwrites the first.

---

## Requirements

- PHP `>= 8.5`
- `momo-framework/kernel`

---

## Installation

Auto-discovered by Momo Framework via `PackageManifest` — no manual registration needed.

```bash
composer require momo-framework/bus
```

---

## Core concept

```
HTTP / gRPC / CLI / GraphQL
           │
           ▼
    Controller / Resolver         (delivery layer — knows about protocol)
           │
           │  new CreateOrderCommand(...)
           ▼
       CommandBus                 (this package)
           │
           ▼
    CreateOrderHandler            (application layer — knows about domain)
           │
           ▼
    Domain / Infrastructure       (pure business logic)
```

Adding a new protocol (gRPC, GraphQL, CLI) means writing a new adapter that dispatches the same `Command` or `Query`. **Handlers never change.**

---

## Usage

### Commands — write side

Define a command as a simple readonly value object:

```php
final readonly class CreateOrderCommand implements CommandInterface
{
    public function __construct(
        public string $customerId,
        public array  $items,
    ) {}
}
```

Define a handler that performs the operation:

```php
final class CreateOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly QueueInterface  $queue,
    ) {}

    public function handle(CommandInterface $command): void
    {
        assert($command instanceof CreateOrderCommand);

        $order = Order::create($command->customerId, $command->items);
        $this->orders->save($order);

        // Heavy work goes to the queue — bus stays synchronous
        $this->queue->push(new SendOrderEmailJob($order->id));
    }
}
```

Dispatch from a controller:

```php
$commandBus->dispatch(new CreateOrderCommand($customerId, $items));
// returns void — fire and forget
```

---

### Queries — read side

Define a query:

```php
final readonly class GetOrderQuery implements QueryInterface
{
    public function __construct(
        public string $orderId,
    ) {}
}
```

Define a handler that returns data:

```php
final class GetOrderHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly OrderRepository $orders,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetOrderQuery);

        return $this->orders->findById($query->orderId);
    }
}
```

Ask from a controller:

```php
$order = $queryBus->ask(new GetOrderQuery($orderId));
// returns whatever the handler returns
```

---

### Registering in a Module ServiceProvider

```php
final class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(CreateOrderHandler::class);
        $this->singleton(GetOrderHandler::class);
    }

    public function boot(): void
    {
        $this->app->make(CommandBusInterface::class)
            ->register(CreateOrderCommand::class, $this->app->make(CreateOrderHandler::class));

        $this->app->make(QueryBusInterface::class)
            ->register(GetOrderQuery::class, $this->app->make(GetOrderHandler::class));
    }
}
```

---

## Development

```bash
# install dependencies
composer install

# run tests
composer test

# run tests with coverage report (requires PCOV)
composer test:coverage

# static analysis — PHPStan level 10
composer stan

# code style check
composer lint

# code style fix
composer lint:fix

# rector — check for upgrades
composer rector:check

# run full CI pipeline locally
composer ci
```

### CI pipeline

```
composer ci
  ├── lint          php-cs-fixer --dry-run
  ├── stan          phpstan level 10
  ├── rector:check  rector --dry-run
  └── test          phpunit
```

---

<div align="center">
  <sub>Part of <a href="https://github.com/momo-framework">Momo Framework</a> — a high-performance, modular PHP framework for building resilient distributed systems.</sub>
</div>