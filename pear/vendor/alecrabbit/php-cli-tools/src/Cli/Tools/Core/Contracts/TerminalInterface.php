<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools\Core\Contracts;

interface TerminalInterface
{
    /**
     * @return int
     */
    public function width(): int;

    /**
     * @return int
     */
    public function height(): int;

    /**
     * @return int
     */
    public function color(): int;
}
