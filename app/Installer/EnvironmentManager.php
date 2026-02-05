<?php

namespace App\Installer;

class EnvironmentManager
{
    /**
     * @var string
     */
    protected $envPath;

    /**
     * Initialize new EnvironmentManager instance.
     *
     * @param string|null $envPath
     */
    public function __construct(?string $envPath = null)
    {
        $this->envPath = $envPath ?? base_path('.env');
    }

    /**
     * Get the memory limit in MB
     *
     * @return int
     */
    public static function getMemoryLimitInMegabytes()
    {
        return \DetachedHelper::getMemoryLimitInMegabytes();
    }

    public static function memoryLimitToBytes($memoryLimit)
    {
        if ('-1' === $memoryLimit) {
            return -1;
        }

        $memoryLimit = strtolower($memoryLimit);
        $max         = strtolower(ltrim($memoryLimit, '+'));

        if (str_starts_with($max, '0x')) {
            $max = \intval($max, 16);
        } elseif (str_starts_with($max, '0')) {
            $max = \intval($max, 8);
        } else {
            $max = (int) $max;
        }

        switch (substr($memoryLimit, -1)) {
            case 't':
                $max *= 1024;
                // no break
            case 'g':
                $max *= 1024;
                // no break
            case 'm':
                $max *= 1024;
                // no break
            case 'k':
                $max *= 1024;
        }

        return $max;
    }

    /**
     * Guess the application URL
     *
     * @return string
     */
    public static function guessUrl()
    {
        $guessedUrl = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 'https' : 'http';
        $guessedUrl .= '://' . $_SERVER['HTTP_HOST'];
        $guessedUrl .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        $guessedUrl = preg_replace('/install.*/', '', $guessedUrl);

        return rtrim($guessedUrl, '/');
    }
}
