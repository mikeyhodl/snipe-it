<?php

namespace App\Helpers;

trait DisablesDebugbar
{
    public function disableDebugbar()
    {
        if (class_exists(\Fruitcake\LaravelDebugbar\Facades\Debugbar::class)) {
            \Fruitcake\LaravelDebugbar\Facades\Debugbar::disable();
        }
    }
}
