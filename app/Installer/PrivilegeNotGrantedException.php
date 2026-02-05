<?php

namespace App\Installer;

use Exception;

class PrivilegeNotGrantedException extends Exception
{
    /**
     * Get the privilege name that was denied
     *
     * @return string
     */
    public function getPriviligeName()
    {
        $re = '/[0-9]+\s([A-Z]+)+\scommand denied/m';

        preg_match($re, $this->getMessage(), $matches);

        return $matches[1] ?? '';
    }
}
