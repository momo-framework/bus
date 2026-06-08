<div align="center">
  <img src="https://avatars.githubusercontent.com/u/255415480?s=200&v=4" alt="Momo Framework" width="120" height="120" />

  <h1>momo-framework/bus</h1>

  <p>
      Синхронные внутрипроцессные CQRS-шины для <a href="https://github.com/momo-framework">Momo Framework</a>.
      Строго типизированный интерфейс диспатча Command и Query в рамках одного цикла запроса.
  </p>

  <p>
    <img src="https://img.shields.io/badge/php-%3E%3D8.5-8892bf.svg" alt="PHP Version" />
    <img src="https://img.shields.io/badge/license-AGPL--3.0-red.svg" alt="License" />
  </p>
  <p>🇷🇺 Русская версия &nbsp;·&nbsp; 🇬🇧 <a href="README.md">English</a></p>
</div>

---

## Обзор

`momo-framework/bus` — два лёгких внутрипроцессных автобуса для разделения операций **записи** и **чтения** по паттерну [CQRS](https://martinfowler.com/bliki/CQRS.html).

| Шина | Назначение | Возврат |
|------|-----------|---------|
| `CommandBus` | Операции записи — создание, обновление, удаление | `void` |
| `QueryBus` | Операции чтения — выборка, список, поиск | `mixed` |

Оба автобуса **всегда синхронны** — обработчики выполняются в том же процессе, в том же цикле запроса. Для асинхронной обработки используй `momo-framework/queue`.

Контракт **один обработчик на сообщение**: регистрация второго обработчика для того же класса молча перезапишет первый.

---

## Требования

- PHP `>= 8.5`
- `momo-framework/kernel`

---

## Установка

Автоматически обнаруживается Momo Framework через `PackageManifest`.

```bash
composer require momo-framework/bus
```

---

## Основная концепция

```
HTTP / gRPC / CLI / GraphQL
           │
           ▼
    Контроллер / Резолвер     (слой доставки — знает о протоколе)
           │
           │  new CreateOrderCommand(...)
           ▼
       CommandBus             (этот пакет)
           │
           ▼
    CreateOrderHandler        (прикладной слой — знает о домене)
           │
           ▼
    Domain / Infrastructure   (чистая бизнес-логика)
```

Добавление нового протокола (gRPC, GraphQL, CLI) означает написание нового адаптера с теми же `Command`/`Query`. **Обработчики никогда не меняются.**

---

## Использование

### Команды — сторона записи

```php
// Объявление команды как иммутабельного value object
final readonly class CreateOrderCommand implements CommandInterface
{
    public function __construct(
        public string $customerId,
        public array  $items,
    ) {}
}

// Обработчик
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

        $this->queue->push(new SendOrderEmailJob($order->id));
    }
}

// Диспатч из контроллера
$commandBus->dispatch(new CreateOrderCommand($customerId, $items));
// возвращает void
```

### Запросы — сторона чтения

```php
final readonly class GetOrderQuery implements QueryInterface
{
    public function __construct(public string $orderId) {}
}

final class GetOrderHandler implements QueryHandlerInterface
{
    public function __construct(private readonly OrderRepository $orders) {}

    public function handle(QueryInterface $query): mixed
    {
        assert($query instanceof GetOrderQuery);
        return $this->orders->findById($query->orderId);
    }
}

// Из контроллера
$order = $queryBus->ask(new GetOrderQuery($orderId));
```

### Регистрация в ServiceProvider модуля

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

## Разработка

```bash
composer install
composer test
composer test:coverage
composer stan
composer lint
composer ci
```
