<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Profiler;

use Codedungeon\PHPCliColors\Color;
use Psr\Log\LogLevel;
use Spiral\Debug\System;
use Spiral\Logger\Event\LogEvent;

/**
 * Forwards all log events into given stream.
 */
class LogStream
{
    // Coloring of messages
    protected const COLORS = [
        LogLevel::DEBUG     => Color::GREEN,
        LogLevel::INFO      => Color::CYAN,
        LogLevel::NOTICE    => Color::YELLOW,
        LogLevel::WARNING   => Color::YELLOW,
        LogLevel::ERROR     => Color::RED,
        LogLevel::CRITICAL  => Color::RED,
        LogLevel::ALERT     => Color::RED,
        LogLevel::EMERGENCY => Color::RED,
    ];

    // Coloring of messages
    protected const MESSAGE_COLORS = [
        LogLevel::DEBUG     => Color::GRAY,
        LogLevel::INFO      => Color::GRAY,
        LogLevel::NOTICE    => Color::YELLOW,
        LogLevel::WARNING   => Color::YELLOW,
        LogLevel::ERROR     => Color::RED,
        LogLevel::CRITICAL  => Color::LIGHT_RED,
        LogLevel::ALERT     => Color::LIGHT_RED,
        LogLevel::EMERGENCY => Color::LIGHT_RED,
    ];

    /** @var resource */
    private $stream;

    /** @var bool */
    private $colorSupport;

    /**
     * @param bool|resource $stream
     * @param bool|null     $colorSupport
     */
    public function __construct($stream = STDERR, bool $colorSupport = null)
    {
        $this->stream = $stream;

        if (is_null($colorSupport)) {
            $this->colorSupport = System::isColorsSupported($this->stream);
        } else {
            $this->colorSupport = $colorSupport;
        }
    }

    /**
     * @param LogEvent $event
     */
    public function __invoke(LogEvent $event)
    {
        if ($this->colorSupport) {
            fwrite($this->stream, $this->renderColored($event));

            return;
        }

        fwrite($this->stream, $this->render($event));
    }

    /**
     * @param LogEvent $event
     * @return string
     */
    protected function render(LogEvent $event): string
    {
        $result = "";
        foreach (explode("\n", $event->getMessage()) as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $result .= sprintf(
                "[%s:%s] %s",
                $this->fetchChannel($event),
                strtoupper($event->getLevel()),
                $line
            ) . "\n";
        }

        return $result;
    }

    /**
     * @param LogEvent $event
     * @return string
     */
    protected function renderColored(LogEvent $event): string
    {
        $result = "";
        foreach (explode("\n", $this->fetchMessage($event)) as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $result .= sprintf(
                "[%s%s%s:%s] %s",
                Color::LIGHT_WHITE,
                $this->fetchChannel($event),
                Color::RESET,
                $this->fetchLevel($event),
                $line
            ) . "\n";
        }

        return $result;
    }

    /**
     * @param LogEvent $event
     * @return string
     */
    protected function fetchChannel(LogEvent $event): string
    {
        $channel = $event->getChannel();
        try {
            if (class_exists($channel)) {
                $channel = (new \ReflectionClass($channel))->getShortName();
            }
        } catch (\Throwable $e) {
            return $channel;
        }

        return $channel;
    }

    /**
     * @param LogEvent $event
     * @return string
     */
    protected function fetchLevel(LogEvent $event): string
    {
        return sprintf(
            "%s%s%s",
            self::COLORS[$event->getLevel()],
            strtoupper($event->getLevel()),
            Color::RESET
        );
    }

    /**
     * @param LogEvent $event
     * @return string
     */
    protected function fetchMessage(LogEvent $event): string
    {
        $message = $event->getMessage();
        if (empty($event->getContext()['query'])) {
            return sprintf(
                "%s%s%s",
                self::MESSAGE_COLORS[$event->getLevel()],
                $message,
                Color::RESET
            );
        }

        // Formatting SQL Query
        \SqlFormatter::$cli_reserved = Color::LIGHT_WHITE;
        \SqlFormatter::$cli_quote = Color::GREEN;
        \SqlFormatter::$cli_boundary = Color::LIGHT_WHITE;

        return \SqlFormatter::highlight($message);
    }
}
