<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 09.08.14
 * Time: 01:20
 */

namespace Pkj\LinuxGenericBackup;


class GenericDatabaseInstructions {

    public function __construct(BackupHandler $handler) {
        $handler->configSpecification->requireConfig('database:array');
        $handler->configSpecification->requireConfig('database.user:string');
        $handler->configSpecification->requireConfig('database.password:string');
        $handler->configSpecification->requireConfig('database.host:string');
        $handler->configSpecification->requireConfig('database.ignore_databases:array');


    }
    

} 