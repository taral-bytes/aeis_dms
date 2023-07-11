<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools;

use const AlecRabbit\CSI;

class Screen
{
    /**
     * Clears screen
     *
     * @return string
     */
    public static function clear(): string
    {
        return CSI . '2J';
    }

    /**
     * Clears screen
     *
     * @return string
     */
    public static function eraseToCursor(): string
    {
        return CSI . '1J';
    }

    /**
     * Clears screen
     *
     * @return string
     */
    public static function eraseFromCursor(): string
    {
        return CSI . '0J';
    }
}
