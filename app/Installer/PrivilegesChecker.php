<?php

namespace App\Installer;

use Illuminate\Database\Connection;

class PrivilegesChecker
{
    /**
     * Initialize PrivilegesChecker instance
     *
     * @param \Illuminate\Database\Connection $connection
     */
    public function __construct(protected Connection $connection)
    {
    }

    /**
     * Check the privileges
     *
     * @throws \App\Installer\PrivilegeNotGrantedException
     *
     * @return void
     */
    public function check()
    {
        $testMethods = $this->getTesterMethods();
        $tester      = new DatabaseTest($this->connection);

        foreach ($testMethods as $test) {
            $tester->{$test}();

            throw_if($tester->getLastError(), new PrivilegeNotGrantedException($tester->getLastError()));
        }
    }

    /**
     * Get the tester methods
     *
     * @return array
     */
    protected function getTesterMethods()
    {
        return [
            'testDropTable',
            'testCreateTable',
            'testSelect',
            'testInsert',
            'testUpdate',
            'testDelete',
            'testAlter',
            'testIndex',
            'testReferences',
        ];
    }
}
