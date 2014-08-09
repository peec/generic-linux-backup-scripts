<?php
namespace Pkj\LinuxGenericBackup\Commands;


use Pkj\LinuxGenericBackup\BackupHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 08.08.14
 * Time: 23:32
 */

class Filesystem extends Command{

    protected function configure() {
        $this->setName("backups:filesystem")
            ->setDescription("Starts a filesystem backup, see config/filesystem.json.")
            ->setDefinition(
                array_merge(array(

                ),BackupHandler::genericCommandArguments("filesystem.json"))
            )
            ->setHelp(<<<EOT
Usage:

<info>./run backups:filesystem</info>

EOT
            );

    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generic = BackupHandler::genericCommandArgumentsParse($input);
        $handler = new BackupHandler($output, $generic, "filesystem.json");
        $handler->allowCmdOverride($input);
        $handler->configSpecification->requireConfig('directories:array');

        $handler->addTask(array($this, 'createBackups'));
        $handler->run();
    }

    public function createBackups (BackupHandler $handler) {
        $createdBackupArchives = array();
        foreach($handler->config['directories'] as $name => $backup_paths) {
            $bpath = $handler->getBackupFilePath($name);
            $backup_paths = (array)$backup_paths;
            $cmd = "tar -zcf $bpath -P " . implode(' ', $backup_paths);
            $handler->doExec($cmd);
            $createdBackupArchives[] = $bpath;
        }
        return $createdBackupArchives;
    }


} 