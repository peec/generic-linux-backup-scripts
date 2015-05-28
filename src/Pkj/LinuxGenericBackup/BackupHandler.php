<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 08.08.14
 * Time: 23:37
 */

namespace Pkj\LinuxGenericBackup;


use Pkj\LinuxGenericBackup\Notifications\NotificationManagerExtension;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackupHandler {

    const DATE_FORMAT_REGEX = '[A-Za-z_.\-]';
    const SETTING_OVERRIDE_CMD_PREFIX = 'setting.';


    /**
     * @var array Tasks that should be run.
     */
    private $tasks = array();

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface Output interface, passed from commands.
     */
    private $output;

    /**
     * @var array|mixed Array of configuration.
     */
    public $config = array();


    public $container;

    public $longNotificationMessage = "";

    public function __construct($container) {
        $this->container = $container;
    }



    /**
     * Adds a new task.
     * @param callable $callback Task that runs.
     */
    public function addTask (callable $callback) {
        $this->tasks[] = $callback;
    }


    /**
     * Runs the procedure.
     */
    public function run () {
        $startTime = time();

        if (!file_exists($this->backupFolder)) {
            $this->doExec("mkdir -p {$this->backupFolder}");
        }

        // Here even in test check this.
        if ($this->config['test'] && !file_exists($this->backupFolder)) {
            throw new \Exception("{$this->backupFolder} must exist and be a directory in test mode.");
        }


        $createdFiles = array();
        foreach($this->tasks as $task) {
            $taskResult = call_user_func_array($task, array($this));
            if (is_array($taskResult)) {
                $createdFiles = array_merge($taskResult);
            }
        }

        $this->removeOldBackups($createdFiles);


        if ($this->config['test']) {
            $this->out("<info>Compiled configuration:</info>");
            $this->output->write(var_export($this->config, true));
            $this->out("<info>Test procedure done.</info>");
        } else {
            $this->out("<info>Backup procedure done.</info>");
        }

        if ($this->config['notifications-when-done']) {
            $doneTime = time()  - $startTime;

            $baseMessage = "Backups created in {$doneTime}s. A total of ".count($createdFiles)." tar archives created.";

            $longMessage = $baseMessage . "\n=====\nTar Archives Created:\n======\n" . implode("\n", $createdFiles);
            $longMessage .= "\n======\nINFO:\n======\n" . $this->longNotificationMessage;

            $this->container->get('notification.manager')->info(
                "{$baseMessage}.Last file was " . basename(end($createdFiles)),
                "{$longMessage}"

            );
        }


    }


    /**
     * Passed to usort..
     * @param $file1
     * @param $file2
     * @return int
     */
    public function sortByCreationDateDesc($file1,$file2) {
        $time1 = filemtime($file1);
        $time2 = filemtime($file2);
        if ($time1 == $time2) {
            return 0;
        }
        return ($time1 < $time2) ? 1 : -1;
    }

    /**
     * Both outputs to console and logs to log file.
     * @param $o
     */
    public function out ($o) {
        $path = $this->backupFolder;
        $msg = '['.date('Y-m-d H:i:s') . '] : ' . $o;
        if (!$this->config['test']) {
            file_put_contents($path . '/backup.log', $msg . "\n", FILE_APPEND);
        }
        $this->longNotificationMessage .= $msg . "\n";
        $this->output->writeln($msg);
    }

    /**
     * Executes something on server.
     * @param $e The command to execute, normally something to add a .gz. file.
     * @param bool $stopOnError
     * @param function Callback formatter of Output.
     * @throws \Exception
     * @return array
     */
    public function doExec ($e, $stopOnError=true, $formatter=null) {
        $output = array();
        if ( !$this->config['test']) {
            exec($e, $output, $return);
        } else {
            $return = 0;
        }
        $message = $e;
        if ($formatter) {
            $message = call_user_func_array($formatter, array($e));
        }

        if ($return) {
            $msg = "Error (#$return): $message\nOutput:\n" . implode("\n", $output);
            if ($stopOnError) {
                throw new \Exception($msg);
            } else {
                $this->out($msg);
            }
        }
        $this->out($message);

        $this->longNotificationMessage .= implode("\n", $output);
        return $output;
    }


    /**
     * Removes backups if amount_of_backups is not 0.
     */
    public function removeOldBackups () {
        $path = $this->backupFolder;
        $config = $this->config;

        // If we set amount_of_backups to 0, never remove backups..
        if ($this->config['amount_of_backups'] === 0) {
            $this->out("<comment>amount_of_backups is set to 0, skipping deletion of old backups.</comment>");
            return;
        }

        $prefix = $this->getBackupFilePrefix();
        $files = glob("$path/*-$prefix{$config['server_name']}-*.gz");
        usort($files, array($this, 'sortByCreationDateDesc'));

        $backup_count = [];
        foreach($files as $file) {

            // Validation pattern, extremely important to not delete other files by mistake...
            // Generates forexample: /^.*?[\-]{1}hourly-prod-(.*?).gz/ when ./linuxbackups backups:filesystem --backup-file-prefix="hourly"
            // Generates forexample: /^.*?[\-]{1}prod-(.*?).gz/ when ./linuxbackups backups:filesystem
            $dirregex = dirname($file);
            $pattern = "#^$dirregex/(.*?)-$prefix{$config['server_name']}-(.*?).gz#";

            if (preg_match($pattern, $file, $matches)) {
                $dateFromFile = $matches[1];
                $wp_name = $matches[2];

                // Check date.
                $date = \DateTime::createFromFormat($config['backup-date-format'], $dateFromFile);
                if ($date) {
                    if (!isset($backup_count[$wp_name])) {
                        $backup_count[$wp_name] = 0;
                    }
                    $backup_count[$wp_name]++;
                    if ($backup_count[$wp_name] > $config['amount_of_backups']) {
                        $backup_count[$wp_name]--;
                        $this->doExec("rm -f $file");
                    } else {
                        $msg = "($wp_name) Keeping file $file";
                        $this->debug($msg);
                        $this->longNotificationMessage .= "$msg\n";
                    }
                } else {
                    $this->debug("Date from file $dateFromFile does not match {$config['backup-date-format']}.");
                }
            } else {
                $this->debug("$pattern does not match $file");
            }
        }

        $thebackupcounts = [];
        foreach($backup_count as $name => $count) {
            $thebackupcounts[] = "$name=$count";
        }

        $this->out("Backup count: (".implode(', ', $thebackupcounts).").");

    }

    public function debug ($msg) {
        if ($this->config['debug']) {
            $this->out("DEBUG: $msg");
        }
    }

    /**
     * Gets file path to tar file for the TASK.
     * @param $name
     * @return string
     */
    public function getBackupFilePath ($name) {
        $path = $this->backupFolder;
        $config = $this->config;

        $time = time();
        $date = date($config['backup-date-format'], $time);
        $prefix = $this->getBackupFilePrefix();
        $file = "{$path}/$date-$prefix{$config['server_name']}-$name.gz";
        return $file;
    }

    private function getBackupFilePrefix () {
        $config = $this->config;
        $prefix = $config['backup-file-prefix'] ? $config['backup-file-prefix'] . '-' : '';
        return $prefix;
    }



} 
