<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools;

use const AlecRabbit\ENV_COLORTERM;
use const AlecRabbit\ENV_DOCKER_TERM;
use const AlecRabbit\ENV_TERM;
use const AlecRabbit\NEEDLE_256_COLOR;
use const AlecRabbit\NEEDLE_TRUECOLOR;
use const AlecRabbit\XTERM;

class EnvCheck
{
    /**
     * @return bool
     */
    public static function isXterm(): bool
    {
        return
            static::checkEnvVariable(ENV_TERM, XTERM) ||
            static::checkEnvVariable(ENV_DOCKER_TERM, XTERM);
    }

    /**
     * @param string $varName
     * @param string $checkFor
     * @return bool
     */
    protected static function checkEnvVariable(string $varName, string $checkFor): bool
    {
        if ($t = getenv($varName)) {
            return
                false !== strpos($t, $checkFor);
        }
        return false;
    }

    /**
     * @return bool
     */
    public static function has256ColorSupport(): bool
    {
        return
            static::checkEnvVariable(ENV_TERM, NEEDLE_256_COLOR) ||
            static::checkEnvVariable(ENV_DOCKER_TERM, NEEDLE_256_COLOR);
    }

    /**
     * @return bool
     */
    public static function hasTrueColorSupport(): bool
    {
        return
            static::checkEnvVariable(ENV_COLORTERM, NEEDLE_TRUECOLOR);
    }
}
