<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 08.08.14
 * Time: 23:37
 */

namespace Pkj\LinuxGenericBackup;


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
     * @var The config file.
     */
    private $configFile;

    /**
     * @var array|mixed Array of configuration.
     */
    public $config = array();

    /**
     * @var JsonFileExpressionParser
     */
    public $configSpecification;


    public $configCmdOverride = array();

    /**
     * Creates config specification and sets up the object.
     * @param OutputInterface $output
     * @param $configFile
     */
    public function __construct (OutputInterface $output, array $backupHandlerArguments, $configFile) {
        $this->output = $output;
        $this->configFile = "{$backupHandlerArguments['config-path']}$configFile";
        $this->configSpecification = new JsonFileExpressionParser($this->configFile, array_merge($backupHandlerArguments, array(
            'server_name' => php_uname('n')
        )));

        $this->config = $this->configSpecification->get();


        $this->configSpecification->requireConfig('backup_path:string');
        $this->configSpecification->requireConfig('amount_of_backups:int');

        if (!$this->config['server_name']) {
            $this->configSpecification->requireConfig('server_name:string');
        }

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
        // Allow cmd overrides..

        $linearConfig = array();

        // Work with overrides...

        // Convert normal config to linear d.d.d.d.
        Utils::arrayToLinear($this->config, $linearConfig);
        // Check for cmd overrides....
        foreach($this->configCmdOverride as $k => $v) {
            $linearConfig[$k] = $v;
        }
        // Convert back to array.
        $this->config = Utils::linearToArray($linearConfig, '.');
        // Parse varibles in config..
        $this->config = Utils::giveArrayOfValuesVariables($this->config, $linearConfig);
        // Set backup path.
        $this->backupFolder = $this->config['backup_path'];



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
        $msg = '['.date('Y-m-d H:i:s') . '] : ' . $o . "\n";
        if (!$this->config['test']) {
            file_put_contents($path . '/backup.log', $msg, FILE_APPEND);
        }
        $this->output->writeln($msg);
    }

    /**
     * Executes something on server.
     * @param $e The command to execute, normally something to add a tar.gz. file.
     * @param bool $stopOnError
     * @throws \Exception
     */
    public function doExec ($e, $stopOnError=true) {
        if ( !$this->config['test']) {
            exec($e, $output, $return);
        } else {
            $return = 0;
        }


        if ($return) {
            $msg = "Error (#$return): $e\nOutput:\n" . implode("\n", $output);
            if ($stopOnError) {
                throw new \Exception($msg);
            } else {
                $this->out($msg);
            }
        }
        $this->out($e);
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
        $files = glob("$path/*-$prefix{$config['server_name']}-*.tar.gz");
        usort($files, array($this, 'sortByCreationDateDesc'));

        $backup_count = [];
        foreach($files as $file) {

            // Validation pattern, extremely important to not delete other files by mistake...
            // Generates forexample: /^.*?[\-]{1}hourly-prod-(.*?).tar.gz/ when ./linuxbackups backups:filesystem --backup-file-prefix="hourly"
            // Generates forexample: /^.*?[\-]{1}prod-(.*?).tar.gz/ when ./linuxbackups backups:filesystem
            $dirregex = str_replace('/', '\/', dirname($file));
            $pattern = "/^$dirregex\/(.*?)[\-]{1}$prefix{$config['server_name']}-(.*?).tar.gz/";

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
                        $this->debug("($wp_name) Keeping file $file");
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
        $file = "{$path}/$date-$prefix{$config['server_name']}-$name.tar.gz";
        return $file;
    }

    private function getBackupFilePrefix () {
        $config = $this->config;
        $prefix = $config['backup-file-prefix'] ? $config['backup-file-prefix'] . '-' : '';
        return $prefix;
    }

    public function allowCmdOverride (InputInterface $input) {
        foreach($input->getOptions() as $k => $v) {
            if (0 === strpos($k, self::SETTING_OVERRIDE_CMD_PREFIX)) {
                $realkey = substr($k, strlen(self::SETTING_OVERRIDE_CMD_PREFIX));
                if ($v) {
                    $this->configCmdOverride[$realkey] = $v;
                }
            }
        }

    }

    /**
     * @return array Generic Command arguments for this handler.
     */
    static public function genericCommandArguments ($configFile = '') {
        $defaultConfigPath = APP_ROOT_DIR . "/config";
        $args = array(
            new InputOption('env', null, InputOption::VALUE_OPTIONAL,
                'Loads config files prefixed with this env variable, example: --env="prod" will load config/prod-database.json for the database backup.', false),
            new InputOption('config-path', 'cp', InputOption::VALUE_REQUIRED,
                'Where should we load config files from?', $defaultConfigPath),
            new InputOption('backup-file-prefix', 'bfp', InputOption::VALUE_OPTIONAL,
                'This is useful if you have different cron jobs doing forexample daily and hourly backups, set this to daily for one and hourly for another.'),
            new InputOption('test', null, InputOption::VALUE_NONE,
                'Test configuration, useful for testing before you actually run the scripts'),
            new InputOption('backup-date-format', null, InputOption::VALUE_REQUIRED,
                'Dateformat of the backup files, see valid values on php.net/date. (must be values: ' . self::DATE_FORMAT_REGEX . ')', 'Y-m-d_His'),
            new InputOption('debug', null, InputOption::VALUE_NONE,
                'Enables debug information.'),
        );
        if ($configFile && $data = json_decode(file_get_contents($defaultConfigPath . '/' . $configFile), true)) {
            $linearConfig = array();
            Utils::arrayToLinear($data, $linearConfig);
            foreach($linearConfig as $k => $v) {
                $type = InputOption::VALUE_OPTIONAL;
                $key = self::SETTING_OVERRIDE_CMD_PREFIX.$k;
                $args[] = new InputOption($key, null, $type,
                    "Override $k in settings.");

            }
        }

        return $args;
    }


    static public function genericCommandArgumentsParse(InputInterface $input) {
        $ar = array();
        $ar['config-path'] = $input->getOption('config-path');

        if (!file_exists($ar['config-path'])) {
            throw new \Exception("config-path {$ar['config-path']} does not exist.");
        }
        $ar['test'] = $input->getOption('test');
        $ar['backup-file-prefix'] = $input->getOption('backup-file-prefix');
        $ar['backup-date-format'] = $input->getOption('backup-date-format');
        $ar['debug'] = $input->getOption('debug');

        if (!preg_match('/^' . self::DATE_FORMAT_REGEX . '+$/', $ar['backup-date-format'])) {
            throw new \Exception("backup-date-format format is invalid, must be one of: " . self::DATE_FORMAT_REGEX);
        }

        $ar['config-path'] = $ar['config-path'] . '/' . ($input->getOption('env') ? $input->getOption('env') . '-' : '');


        return $ar;
    }

} 