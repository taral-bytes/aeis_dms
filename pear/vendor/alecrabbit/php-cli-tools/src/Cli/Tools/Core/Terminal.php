<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools\Core;

use AlecRabbit\Cli\Tools\Core\Contracts\StaticTerminalInterface;
use AlecRabbit\Cli\Tools\EnvCheck;
use AlecRabbit\Cli\Tools\Stream;
use const AlecRabbit\COLOR256_TERMINAL;
use const AlecRabbit\COLOR_TERMINAL;
use const AlecRabbit\NO_COLOR_TERMINAL;
use const AlecRabbit\TRUECOLOR_TERMINAL;

/**
 * Class Terminal
 * @author AlecRabbit
 */
class Terminal extends AbstractXTermTerminal implements StaticTerminalInterface
{

    /** {@inheritdoc} */
    public static function setTitle(string $title): string
    {
        return
            static::isXterm() ? "\033]0;{$title}\007" : '';
    }

    /** {@inheritdoc} */
    public static function width(bool $recheck = false): int
    {
        if (null !== static::$width && true !== $recheck) {
            return static::$width;
        }
        return
            static::$width = static::getWidth();
    }

    /** {@inheritdoc} */
    public static function height(bool $recheck = false): int
    {
        if (null !== static::$height && true !== $recheck) {
            return static::$height;
        }
        return
            static::$height = static::getHeight();
    }

    /** {@inheritdoc} */
    public static function colorSupport($stream = null): int
    {
        $colorSupport = NO_COLOR_TERMINAL;
        if (false === $stream) {
            return $colorSupport;
        }
        if (Stream::hasColorSupport($stream)) {
            $colorSupport = COLOR_TERMINAL;
            if (EnvCheck::has256ColorSupport()) {
                $colorSupport = COLOR256_TERMINAL;
            }
            if (EnvCheck::hasTrueColorSupport()) {
                $colorSupport = TRUECOLOR_TERMINAL;
            }
        }
        return $colorSupport;
    }
}
