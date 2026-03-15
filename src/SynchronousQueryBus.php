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

use Momo\Bus\Contracts\QueryBusInterface;
use Momo\Bus\Contracts\QueryHandlerInterface;
use Momo\Bus\Contracts\QueryInterface;
use RuntimeException;

final class SynchronousQueryBus implements QueryBusInterface
{
    /** @var array<class-string<QueryInterface>, QueryHandlerInterface> */
    private array $handlers = [];

    public function ask(QueryInterface $query): mixed
    {
        $class = $query::class;

        if (! isset($this->handlers[$class])) {
            throw new RuntimeException('No handler registered for query: ' . $class);
        }

        return $this->handlers[$class]->handle($query);
    }

    public function register(string $queryClass, QueryHandlerInterface $handler): void
    {
        $this->handlers[$queryClass] = $handler;
    }
}
