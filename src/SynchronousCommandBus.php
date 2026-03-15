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
use Momo\Bus\Contracts\CommandHandlerInterface;
use Momo\Bus\Contracts\CommandInterface;
use RuntimeException;

final class SynchronousCommandBus implements CommandBusInterface
{
    /** @var array<class-string<CommandInterface>, CommandHandlerInterface> */
    private array $handlers = [];

    public function dispatch(CommandInterface $command): void
    {
        $class = $command::class;

        if (! isset($this->handlers[$class])) {
            throw new RuntimeException('No handler registered for command: ' . $class);
        }

        $this->handlers[$class]->handle($command);
    }

    public function register(string $commandClass, CommandHandlerInterface $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }
}
