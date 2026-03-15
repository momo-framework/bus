<?php

/**
 * Part of Momo Framework.
 *
 * @copyright Vahe Sargsyan <w33bvGL>
 * @license   AGPL-3.0-or-later <https://www.gnu.org/licenses/agpl-3.0.html>
 * @link      https://github.com/momo-framework
 */

declare(strict_types=1);

namespace Momo\Bus\Contracts;

interface CommandBusInterface
{
    public function dispatch(CommandInterface $command): void;

    /**
     * @param class-string<CommandInterface> $commandClass
     */
    public function register(string $commandClass, CommandHandlerInterface $handler): void;
}
