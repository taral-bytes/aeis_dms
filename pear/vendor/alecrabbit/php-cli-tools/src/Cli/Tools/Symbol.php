<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools;

use const AlecRabbit\CSI;

class Symbol
{
    public static function insert(int $count = 1): string
    {
        return CSI . "{$count}@";
    }

    public static function delete(int $count = 1): string
    {
        return CSI . "{$count}P";
    }

    public static function replaceWithSpace(int $count = 1): string
    {
        return CSI . "{$count}X";
    }
}
