<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools;

use const AlecRabbit\CSI;
use const AlecRabbit\ESC;

/**
 * Class Cursor
 *
 * @link https://docs.microsoft.com/en-us/windows/console/console-virtual-terminal-sequences
 * @link https://www.xfree86.org/4.8.0/ctlseqs.html
 */
class Cursor
{
    /**
     * Show cursor sequence
     *
     * @return string
     */
    public static function show(): string
    {
        return CSI . '?25h';
    }

    /**
     * Hide cursor sequence
     *
     * @return string
     */
    public static function hide(): string
    {
        return CSI . '?25l';
    }

    /**
     * Move cursor up sequence
     *
     * @param int $rows
     * @return string
     */
    public static function up(int $rows = 1): string
    {
        return CSI . "{$rows}A";
    }

    /**
     * Move cursor up to begin of the line sequence
     *
     * @param int $rows
     * @return string
     */
    public static function upLine(int $rows = 1): string
    {
        return CSI . "{$rows}F";
    }

    /**
     * Move cursor down to begin of the line sequence
     *
     * @param int $rows
     * @return string
     */
    public static function downLine(int $rows = 1): string
    {
        return CSI . "{$rows}E";
    }

    /**
     * Move cursor down sequence
     *
     * @param int $rows
     * @return string
     */
    public static function down(int $rows = 1): string
    {
        return CSI . "{$rows}B";
    }

    /**
     * Move cursor forward sequence
     *
     * @param int $cols
     * @return string
     */
    public static function forward(int $cols = 1): string
    {
        return CSI . "{$cols}C";
    }

    /**
     * Move cursor back sequence
     *
     * @param int $cols
     * @return string
     */
    public static function back(int $cols = 1): string
    {
        return CSI . "{$cols}D";
    }

    /**
     * Move cursor to sequence
     *
     * @param int $col
     * @param int $row
     * @return string
     */
    public static function goTo(int $col = 1, int $row = 1): string
    {
        return CSI . "{$row};{$col}f";
    }

    /**
     * Move cursor to position in current line sequence
     *
     * @param int $col
     * @return string
     */
    public static function absX(int $col = 1): string
    {
        return CSI . "{$col}G";
    }

    /**
     * Move cursor to position in current column sequence
     *
     * @param int $row
     * @return string
     */
    public static function absY(int $row = 1): string
    {
        return CSI . "{$row}d";
    }

    /**
     * Save cursor position sequence
     *
     * @return string
     */
    public static function savePosition(): string
    {
        return CSI . 's';
    }

    /**
     * Restore cursor position sequence
     *
     * @return string
     */
    public static function restorePosition(): string
    {
        return CSI . 'u';
    }

    /**
     * Save cursor position and attributes sequence
     *
     * @return string
     */
    public static function save(): string
    {
        return ESC . '7';
    }

    /**
     * Restore cursor position and attributes sequence
     *
     * @return string
     */
    public static function restore(): string
    {
        return ESC . '8';
    }
}
