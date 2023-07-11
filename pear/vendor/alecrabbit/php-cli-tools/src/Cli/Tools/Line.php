<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools;

use const AlecRabbit\CSI;

class Line
{
    /**
     * Erase from the current cursor position (inclusive) to the end of the line
     *
     * @return string
     */
    public static function eraseFromCursor(): string
    {
        return CSI . '0K';
    }

    /**
     * Erase from the beginning of the line up to the current cursor position (inclusive)
     *
     * @return string
     */
    public static function eraseToCursor(): string
    {
        return CSI . '1K';
    }

    /**
     * Erase from the beginning of the line up to the current cursor position (inclusive)
     *
     * @return string
     */
    public static function erase(): string
    {
        return CSI . '2K';
    }

    /**
     * @param int $num
     * @return string
     */
    public static function insert(int $num = 1): string
    {
        return CSI . "{$num}L";
    }

    /**
     * @param int $num
     * @return string
     */
    public static function delete(int $num = 1): string
    {
        return CSI . "{$num}M";
    }
}
