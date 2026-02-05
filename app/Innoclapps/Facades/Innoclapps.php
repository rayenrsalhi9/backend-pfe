<?php

namespace App\Innoclapps\Facades;

use App\Innoclapps\Application;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool booting(callable $callback)
 *
 * @mixin \App\Innoclapps\Application
 * */
class Innoclapps extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Application::class;
    }
}
