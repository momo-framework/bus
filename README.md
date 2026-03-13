<div align="center">
  <img src="https://avatars.githubusercontent.com/u/255415480?s=200&v=4" alt="Momo Framework" width="96" height="96" />

  <h1>momo-framework/bus</h1>

  <p>Synchronous CQRS Command & Query buses for <a href="https://github.com/momo-framework">Momo Framework</a></p>

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

| Bus | Purpose | Returns |
|---|---|---|
| `CommandBus` | Write operations — create, update, delete | `void` |
| `QueryBus` | Read operations — fetch, list, search | `mixed` |

Both buses enforce a **one-handler-per-message** contract. Registering a second handler for the same message class silently overwrites the first.

---

## Requirements

- PHP `>= 8.5`
- `momo-framework/kernel`
- `momo-framework/contracts`

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
    ) {}

    public function handle(CommandInterface $command): void
    {
        assert($command instanceof CreateOrderCommand);

        $this->orders->save(
            Order::create($command->customerId, $command->items)
        );
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
        $this->getContainerBuilder()
            ->register(CreateOrderHandler::class)
            ->setAutowired(true)
            ->setPublic(true);

        $this->getContainerBuilder()
            ->register(GetOrderHandler::class)
            ->setAutowired(true)
            ->setPublic(true);
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

## Why synchronous?

This bus runs **in-process** — handlers execute in the same PHP process, in the same request cycle. No queues, no retries, no async complexity.

```
Request → CommandBus → Handler → Response
          (same process, same memory, same transaction)
```

When you need async processing, publish a **Domain Event** from inside your handler and let a queue consumer handle it. The bus itself stays simple and predictable.

```php
// Handler publishes an event — bus stays synchronous
public function handle(CommandInterface $command): void
{
    $order = Order::create(...);
    $this->orders->save($order);
    
    $this->eventBus->dispatch(new OrderCreated($order->id)); // ← async happens here
}
```

---

## Swapping the implementation

The bus is bound to an interface. To replace `SynchronousCommandBus` with an async implementation — change one line in your `ServiceProvider`, nothing else:

```php
// Default
$container->setAlias(CommandBusInterface::class, SynchronousCommandBus::class);

// Your async implementation
$container->setAlias(CommandBusInterface::class, AmqpCommandBus::class);
```

All handlers, controllers, and modules remain untouched.

---

## Contracts

| Interface | Implementation | Package |
|---|---|---|
| `CommandBusInterface` | `SynchronousCommandBus` | `momo-framework/bus` |
| `QueryBusInterface` | `SynchronousQueryBus` | `momo-framework/bus` |
| `CommandInterface` | your command classes | `momo-framework/contracts` |
| `QueryInterface` | your query classes | `momo-framework/contracts` |
| `CommandHandlerInterface` | your handler classes | `momo-framework/contracts` |
| `QueryHandlerInterface` | your handler classes | `momo-framework/contracts` |

---

## Development

```bash
# install dependencies
composer install

# run tests
composer test

# run tests with coverage report (requires PCOV or Xdebug)
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

## Quality

| Metric | Result |
|---|---|
| Test coverage | 100% lines, 100% methods |
| PHPStan level | 10 (max) |
| PHP version | 8.5+ strict types |
| Mutation testing | excluded (integration-only provider) |

---

<div align="center">
  <sub>Part of <a href="https://github.com/momo-framework">Momo Framework</a> — high-performance modular e-commerce engine</sub>
</div>