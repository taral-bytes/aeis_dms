<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools\Core\Contracts;

/**
 * Interface TerminalInterface
 *
 * @author AlecRabbit
 */
interface StaticTerminalInterface
{
    /**
     * @param bool $recheck
     * @return int
     */
    public static function width(bool $recheck = false): int;

    /**
     * @param bool $recheck
     * @return int
     */
    public static function height(bool $recheck = false): int;

    /**
     * Returns set title ESC sequence
     *
     * @param string $title
     * @return string
     */
    public static function setTitle(string $title): string;

    /**
     * Returns color support level
     *
     * @param mixed $stream
     * @return int
     */
    public static function colorSupport($stream = null): int;
}
