<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools\Core;

use AlecRabbit\Cli\Tools\EnvCheck;

abstract class AbstractXTermTerminal extends AbstractTerminal
{
    /** @var null|bool */
    protected static $isXterm;

    /**
     * @return bool
     */
    protected static function isXterm(): bool
    {
        if (null !== static::$isXterm) {
            return static::$isXterm;
        }
        return
            static::$isXterm = EnvCheck::isXterm();
    }
}
