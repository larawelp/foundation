<?php

namespace LaraWelP\Foundation\Events;

class WhenFolioRegisters
{
    public const EVENT_NAME = 'larawelp.register.folio';

    public static function provide(callable $callback): void
    {
        app('events')->listen(self::EVENT_NAME, $callback);
    }
}