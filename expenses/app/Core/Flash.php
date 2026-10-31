<?php

declare(strict_types=1);

namespace App\Core;

class Flash
{
    private const SESSION_KEY = '_flash_messages';

    public static function add(string $type, string $message): void
    {
        Session::start();
        $messages = Session::get(self::SESSION_KEY, []);
        $messages[] = [
            'type' => $type, // 'success', 'danger', 'warning', 'info'
            'message' => $message,
        ];
        Session::set(self::SESSION_KEY, $messages);
    }

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('danger', $message);
    }

    public static function warning(string $message): void
    {
        self::add('warning', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    /**
     * @return array<int, array{type: string, message: string}>
     */
    public static function getMessages(): array
    {
        Session::start();
        $messages = Session::get(self::SESSION_KEY, []);
        Session::remove(self::SESSION_KEY);
        return is_array($messages) ? $messages : [];
    }
}
