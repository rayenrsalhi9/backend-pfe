<?php
namespace App\Innoclapps\Facades;

use Illuminate\Support\Facades\Facade;
use App\Innoclapps\Changelog\Logging as BaseLogging;

class ChangeLogger extends Facade
{
    /**
     * Indicates the model log name
     */
    const MODEL_LOG_NAME = 'model';

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BaseLogging::class;
    }
}
