<?php

/**
 * Part of Momo Framework.
 *
 * © Momo Framework
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Unauthorized copying, modification, or distribution of this file,
 * via any medium, is strictly prohibited without prior written permission
 * from the copyright holder.
 *
 * @author    Vahe Sargsyan <w33bvGL>
 * @copyright Momo Framework
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
