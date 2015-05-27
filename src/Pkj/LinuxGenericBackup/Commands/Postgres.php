<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 08.08.14
 * Time: 23:45
 */
namespace Pkj\LinuxGenericBackup\Commands;
use Pkj\LinuxGenericBackup\BackupHandler;
use Pkj\LinuxGenericBackup\GenericDatabaseInstructions;
use Pkj\LinuxGenericBackup\JsonFileExpressionParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
class Postgres extends BaseCommand{
    protected function configure() {
        $this->setName("backups:postgres")
            ->setDescription("Starts database backup for postgres databases, see config/postgres.json.")
            ->setDefinition(
                array_merge(array(
                ),BackupHandler::genericCommandArguments("postgres.json")
            ))
            ->setHelp(<<<EOT
Usage:
<info>./run backups:postgres</info>
EOT
            );
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $handler = $this->container->get('backup.handler');
            $generic = BackupHandler::genericCommandArgumentsParse($input);
            $handler->injectInterfaces($output, $generic, "postgres.json");
            $handler->allowCmdOverride($input);
            new GenericDatabaseInstructions($handler);
            $handler->addTask(array($this, 'createBackups'));
            $handler->run();
        } catch(\Exception $e) {
            $this->container->get('notification.manager')->error("Error creating backups: " . $e->getMessage());
            throw $e;
        }
    }
    public function createBackups (BackupHandler $handler) {
        $createdBackupArchives = array();
        $config =  $handler->config['postgres'];
        // Get databases in array..
        $databases = array();
        
        $output = exec("su postgres -c 'psql -qlt | cut -d \| -f 1'", $output);
        $databases = array_filter($output, function ($dbname) {
            $dbname = trim($dbname);
            if (!$dbname) return false;
            foreach ($config['ignore_databases'] as $ignorepattern) {
                if (preg_match('^'.$ignorepattern.'$', $dbname)) {
                    return false;
                }
            }
            return true;
        });
        
        
        // Run.
        foreach($databases as $db) {
            $bpath = $handler->getBackupFilePath($db);
            $cmd = "mysqldump --force --opt --user={$config['user']} --password={$config['password']} --databases $db  | gzip -c >  $bpath";
            $handler->doExec($cmd, false, function ($msg) {
                return preg_replace("/password=(.*?)--databases/i", 'password="***" --databases', $msg);
            });
            $createdBackupArchives[] = $bpath;
        }
        return $createdBackupArchives;
    }
} 
