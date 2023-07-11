<?php declare(strict_types=1);

namespace AlecRabbit\Cli\Tools;

use const AlecRabbit\ENV_ANSICON;
use const AlecRabbit\ENV_CON_EMU_ANSI;
use const AlecRabbit\ENV_TERM;
use const AlecRabbit\ENV_TERM_PROGRAM;
use const AlecRabbit\XTERM;

class Stream
{

    /**
     * Returns true if the stream supports colorization.
     *
     * Colorization is disabled if not supported by the stream:
     *
     * This is tricky on Windows, because Cygwin, Msys2 etc emulate pseudo
     * terminals via named pipes, so we can only check the environment.
     *
     * Reference: Composer\XdebugHandler\Process::supportsColor
     * https://github.com/composer/xdebug-handler
     *
     * Reference: Symfony\Component\Console\Output\StreamOutput::hasColorSupport()
     * https://github.com/symfony/console
     *
     * @param null|bool|resource $stream
     * @return bool true if the stream supports colorization, false otherwise
     */
    public static function hasColorSupport($stream = null): bool
    {
        $stream = self::refineStream($stream);

        if ('Hyper' === getenv(ENV_TERM_PROGRAM)) {
            // @codeCoverageIgnoreStart
            return true;
            // @codeCoverageIgnoreEnd
        }

        // @codeCoverageIgnoreStart
        if ('\\' === \DIRECTORY_SEPARATOR) {
            return static::checkWindowsColorSupport($stream);
        }
        // @codeCoverageIgnoreEnd

        if (\function_exists('stream_isatty')) {
            return @stream_isatty($stream);
        }

        // @codeCoverageIgnoreStart
        if (\function_exists('posix_isatty')) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            return @posix_isatty($stream);
        }

        return static::checkStream($stream);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param null|bool|resource $stream
     * @return resource
     */
    protected static function refineStream($stream = null)
    {
        $stream = $stream ?? \STDOUT;
        self::assertStream($stream);
        return $stream;
    }

    /**
     * @param mixed $stream
     */
    protected static function assertStream($stream): void
    {
        if (!\is_resource($stream)) {
            throw new \RuntimeException(
                'Expecting parameter 1 to be resource, ' . \gettype($stream) . ' given'
            );
        }
    }

    /**
     * @param resource $stream
     * @return bool
     * @codeCoverageIgnore
     */
    protected static function checkWindowsColorSupport($stream): bool
    {
        return (\function_exists('sapi_windows_vt100_support')
                && @sapi_windows_vt100_support($stream))
            || false !== getenv(ENV_ANSICON)
            || 'ON' === getenv(ENV_CON_EMU_ANSI)
            || XTERM === getenv(ENV_TERM);
    }

    /**
     * @param resource $stream
     * @return bool
     * @codeCoverageIgnore
     */
    protected static function checkStream($stream): bool
    {
        $stat = @fstat($stream);
        // Check if formatted mode is S_IFCHR
        return $stat ? 0020000 === ($stat['mode'] & 0170000) : false;
    }
}
