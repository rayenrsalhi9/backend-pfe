<?php

namespace App\Installer;

class PermissionsChecker
{
    /**
     * @var array
     */
    protected $results = [
        'results' => [],
    ];

    /**
     * Check for the folders permissions.
     *
     * @return array
     */
    public function check()
    {
        $folders = config('installer.permissions');

        foreach ($folders as $folder => $permission) {
            if (! ($this->getPermission($folder) >= $permission)) {
                $this->addFileAndSetErrors($folder, $permission, false);
            } else {
                $this->addFile($folder, $permission, true);
            }
        }

        return $this->results;
    }

    /**
     * Get a folder permission.
     *
     * @param $folder
     *
     * @return string
     */
    private function getPermission($folder)
    {
        return substr(sprintf('%o', fileperms(base_path($folder))), -4);
    }

    /**
     * Add the file to the list of results.
     *
     * @param string $folder
     * @param string $permission
     * @param boolean $isSet
     *
     * @return void
     */
    private function addFile($folder, $permission, $isSet)
    {
        array_push($this->results['results'], [
            'folder'     => $folder,
            'permission' => $permission,
            'isSet'      => $isSet,
        ]);
    }

    /**
     * Add the file and set the errors.
     *
     * @param string $folder
     * @param string $permission
     * @param boolean $isSet
     *
     * @return void
     */
    private function addFileAndSetErrors($folder, $permission, $isSet)
    {
        $this->addFile($folder, $permission, $isSet);

        $this->results['errors'] = true;
    }
}
