<?php

declare(strict_types=1);

namespace Momo\Bus;

use Momo\Contracts\Bus\CommandBusInterface;
use Momo\Contracts\Bus\QueryBusInterface;
use Momo\Kernel\Support\ServiceProvider;

class BusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->container->register(SynchronousCommandBus::class)
            ->setPublic(true);

        $this->app->container->register(SynchronousQueryBus::class)
            ->setPublic(true);

        $this->app->container->setAlias(CommandBusInterface::class, SynchronousCommandBus::class)
            ->setPublic(true);

        $this->app->container->setAlias(QueryBusInterface::class, SynchronousQueryBus::class)
            ->setPublic(true);
    }
}
