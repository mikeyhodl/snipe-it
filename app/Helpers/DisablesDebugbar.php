<?php

namespace App\Helpers;

trait DisablesDebugbar
{
    public function disableDebugbar()
    {
        if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            \Barryvdh\Debugbar\Facades\Debugbar::disable();
        }
    }
}
