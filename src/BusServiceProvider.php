<?php

/**
 * Part of Momo Framework.
 *
 * @copyright Vahe Sargsyan <w33bvGL>
 * @license   AGPL-3.0-or-later <https://www.gnu.org/licenses/agpl-3.0.html>
 * @link      https://github.com/momo-framework
 */

declare(strict_types=1);

namespace Momo\Bus;

use Momo\Bus\Contracts\CommandBusInterface;
use Momo\Bus\Contracts\QueryBusInterface;
use Momo\Kernel\Support\ServiceProvider;

/**
 * @codeCoverageIgnore
 */
final class BusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->singleton(SynchronousCommandBus::class);
        $this->singleton(SynchronousQueryBus::class);

        $this->alias(CommandBusInterface::class, SynchronousCommandBus::class);
        $this->alias(QueryBusInterface::class, SynchronousQueryBus::class);
    }
}
