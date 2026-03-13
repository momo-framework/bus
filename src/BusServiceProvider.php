<?php

declare(strict_types=1);

namespace Momo\Bus;

use Momo\Contracts\Bus\CommandBusInterface;
use Momo\Contracts\Bus\QueryBusInterface;
use Momo\Kernel\Support\ServiceProvider;

/**
 * @codeCoverageIgnore
 */
class BusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $container = $this->getContainerBuilder();

        $container->register(SynchronousCommandBus::class)->setPublic(true);
        $container->register(SynchronousQueryBus::class)->setPublic(true);

        $container->setAlias(CommandBusInterface::class, SynchronousCommandBus::class)->setPublic(true);
        $container->setAlias(QueryBusInterface::class, SynchronousQueryBus::class)->setPublic(true);
    }
}
