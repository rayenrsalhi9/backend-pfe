<?php

use Akaunting\Money\Money;
use Akaunting\Money\Currency;
use App\Support\DefaultSettings;
use App\Innoclapps\Facades\Innoclapps;
use App\Innoclapps\Settings\Contracts\Manager as SettingsManager;

if (! function_exists('format_bytes')) {
    /**
     * Format the given bytes in a proper human readable format
     *
     * @param int|float $bytes
     * @param integer $precision
     *
     * @return string
     */
    function format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (! function_exists('timezone')) {
    /**
     * Helper timezone function
     *
     * @return \App\Innoclapps\Timezone
     */
    function timezone()
    {
        return app('timezone');
    }
}

if (! function_exists('tz')) {
    /**
     * Alias to timezone() function
     *
     * @return \App\Innoclapps\Timezone
     */
    function tz()
    {
        return timezone();
    }
}

if (! function_exists('get_generated_lang')) {
    /**
     * Get the application generate language
     *
     * @param null|string $locale
     *
     * @return \stdClass
     */
    function get_generated_lang($locale = null)
    {
        $content = json_decode(
            file_get_contents(config('innoclapps.lang.json'))
        );

        if (is_null($locale)) {
            return $content;
        }

        return tap(new stdClass, function ($localeClass) use ($content, $locale) {
            if (isset($content->{$locale})) {
                $localeClass->{$locale} = $content->{$locale};
            } else {
                foreach ([config('app.locale'), config('app.fallback_locale')] as $fallback) {
                    if (isset($content->{$fallback})) {
                        $localeClass->{$fallback} = $content->{$fallback};

                        break;
                    }
                }
            }
        });
    }
}

if (! function_exists('privacy_url')) {
    /**
     * Application privacy policy url
     *
     * @return string
     */
    function privacy_url()
    {
        return url('/privacy-policy');
    }
}

if (! function_exists('default_setting')) {
    /**
     * Get application default setting
     *
     * @param mixed $setting The setting name
     *
     * @return string|array|\App\Support\DefaultSettings
     */
    function default_setting($setting = null)
    {
        $class = resolve(DefaultSettings::class);

        if (! is_null($setting)) {
            return $class->get($setting);
        }

        return $class;
    }
}

if (! function_exists('settings')) {
    /**
     * Get the settings manager instance.
     *
     * @param string|array|null $driver
     * @param bool $save
     *
     * @return mixed
     */
    function settings($driver = null, $save = true)
    {
        $manager = app(SettingsManager::class);

        if ($driver) {
            if (is_array($driver)) {
                return tap($manager->set($driver), fn ($instance) => $save && $instance->save());
            }

            if (in_array($driver, array_keys(config('settings.drivers')))) {
                return $manager->driver($driver);
            }

            return $manager->get($driver);
        }

        return $manager;
    }
}

if (! function_exists('clean')) {
    /**
     * Clean the given string
     *
     * @param string $dirty
     * @param mixed $config
     *
     * @return string
     */
    function clean($dirty, $config = null)
    {
        return app('purifier')->clean($dirty, $config);
    }
}


if (! function_exists('get_current_process_user')) {
    /**
     * Get the current PHP process user.
     *
     * The function returns the process user not the file owner user like get_current_user()
     *
     * @return string
     */
    function get_current_process_user()
    {
        if (! function_exists('posix_getpwuid')) {
            return get_current_user();
        }

        return posix_getpwuid(posix_geteuid())['name'];
    }
}

if (! function_exists('forgot_password_is_disabled')) {
    /**
     * Check if the forgot password auth feature is disabled
     *
     * @return bool
     */
    function forgot_password_is_disabled()
    {
        return settings('disable_password_forgot') === true;
    }
}

// if (! function_exists('to_money')) {

//     /**
//      * Create new Money instance
//      *
//      * @param string|int|float $value
//      * @param string|\Akaunting\Money\Currency|null $currency
//      *
//      * @return \Akaunting\Money\Money
//      */
//     function to_money($value, string|Currency $currency = null)
//     {
//         if (is_string($currency)) {
//             $currency = new Currency($currency);
//         } elseif (is_null($currency)) {
//             $currency = new Currency(Innoclapps::currency());
//         }

//         if (! is_float($value)) {
//             $value = (float) $value;
//         }

//         return new Money($value, $currency, true);
//     }
// }
