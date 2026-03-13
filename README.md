# momo-framework/bus

Synchronous CQRS Command and Query buses for [Momo Framework](https://github.com/momo-framework).

## What this is

This package provides two in-process, synchronous buses:

- **CommandBus** — dispatches a command to exactly one handler. No return value. Used for write operations (create, update, delete).
- **QueryBus** — asks a query to exactly one handler. Returns a result. Used for read operations.

Both buses enforce a strict one-handler-per-message contract. Registering a second handler for the same class overwrites the first.

## Installation

This package is auto-discovered by Momo Framework via `PackageManifest`. No manual registration needed.

For standalone use:

```bash
composer require momo-framework/bus
```

## Usage

### Commands (write side)

```php
use Momo\Contracts\Bus\CommandBusInterface;
use Momo\Contracts\Bus\CommandInterface;
use Momo\Contracts\Bus\CommandHandlerInterface;

// 1. Define a command
final readonly class CreateOrderCommand implements CommandInterface
{
    public function __construct(
        public string $customerId,
        public array  $items,
    ) {}
}

// 2. Define a handler
final class CreateOrderHandler implements CommandHandlerInterface
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    public function handle(CommandInterface $command): void
    {
        // $command is CreateOrderCommand
        $this->orders->save(Order::create($command->customerId, $command->items));
    }
}

// 3. Register (done in ServiceProvider)
$commandBus->register(CreateOrderCommand::class, $handler);

// 4. Dispatch (from Controller / CLI)
$commandBus->dispatch(new CreateOrderCommand($customerId, $items));
```

### Queries (read side)

```php
use Momo\Contracts\Bus\QueryBusInterface;
use Momo\Contracts\Bus\QueryInterface;
use Momo\Contracts\Bus\QueryHandlerInterface;

// 1. Define a query
final readonly class GetOrderQuery implements QueryInterface
{
    public function __construct(
        public string $orderId,
    ) {}
}

// 2. Define a handler
final class GetOrderHandler implements QueryHandlerInterface
{
    public function __construct(
        private OrderRepository $orders,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        return $this->orders->findById($query->orderId);
    }
}

// 3. Register (done in ServiceProvider)
$queryBus->register(GetOrderQuery::class, $handler);

// 4. Ask (from Controller)
$order = $queryBus->ask(new GetOrderQuery($orderId));
```

### Registering in a Module ServiceProvider

```php
final class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->container->register(CreateOrderHandler::class)
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

## Architecture

```
Presentation (Controller / Console)
        │
        ▼
   CommandBus / QueryBus          ← this package
        │
        ▼
   Handler (Application layer)
        │
        ▼
   Domain / Infrastructure
```

The bus is the boundary between the delivery mechanism (HTTP, gRPC, CLI) and the application logic. Adding a new protocol means writing a new adapter that calls the same bus — the handlers never change.

## Why synchronous

This is an in-process bus — handlers run in the same PHP process, in the same request. There is no queue, no async, no retry logic.

When you need async processing, publish a Domain Event from inside your handler and let a queue consumer handle it. The bus itself stays synchronous and predictable.

## Running tests

```bash
cd packages/bus
composer install
composer test

# with coverage (requires Xdebug or PCOV)
composer test:coverage

# static analysis
composer stan
```

## Contracts

This package implements interfaces from `momo-framework/contracts`:

| Interface             | Implementation          |
|-----------------------|-------------------------|
| `CommandBusInterface` | `SynchronousCommandBus` |
| `QueryBusInterface`   | `SynchronousQueryBus`   |

To swap the implementation (e.g. for an async bus), bind a different class to the interface in your ServiceProvider — nothing else changes.
