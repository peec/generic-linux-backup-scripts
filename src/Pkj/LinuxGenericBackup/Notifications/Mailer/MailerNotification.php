<?php
/**
 * Created by PhpStorm.
 * User: peecdesktop
 * Date: 11.08.14
 * Time: 01:08
 */

namespace Pkj\LinuxGenericBackup\Notifications\Mailer;


use Pkj\LinuxGenericBackup\Notifications\LongNotificationInterface;

class MailerNotification implements LongNotificationInterface{

    private $config;

    public function __construct ( $config) {

        $this->config = array_merge(array(
            'smtp' => array(
                'port' => 25,
                'encryption' => 'tls'
            ),
            'sendmail' => array(
                '-bs'
            ),
            'subject' => 'Backups: %s',
            'transport' => 'mail'
        ),$config);
    }

    public function error($msg) {
        $this->send($msg, true);
    }

    public function info($msg) {
        $this->send($msg);
    }

    public function send ($msg, $error = false) {

        $config = $this->config;

        switch($config['transport']) {
            case "gmail":
            case "smtp":
                $options = $config[$config['transport']];

                if ($config['transport'] == 'gmail') {
                    $options['host'] = 'smtp.gmail.com';
                    $options['encryption'] = 'ssl';
                    $options['port'] = 465;
                    $config['from'] = $options['username'];
                }
                $transport = \Swift_SmtpTransport::newInstance($options['host'], $options['port']);

                if (!isset($options['username']) || !isset($options['password'])) {
                    throw new \Exception ("mailer.{$config['transport']}.username and mailer.{$config['transport']}.password must be configured.");
                }

                $transport
                    ->setUsername($options['username'])
                    ->setPassword($options['password']);

                $transport->setEncryption($options['encryption']);

                if(isset($options['auth_mode'])) {
                    $transport->setAuthMode($options['auth_mode']);
                }

            break;
            case 'sendmail':
                $options = $config[$config['transport']];
                $transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail ' . $config['arguments']);
                break;
            case 'mail':
                $transport = Swift_MailTransport::newInstance();
                break;
            default:
                throw new \Exception (" Error, unknown transport {$config['transport']}. Valid transport types are gmail,smtp.");
        }

        $mailer = \Swift_Mailer::newInstance($transport);

        $message = \Swift_Message::newInstance(sprintf($config['subject'], ($error ? 'ERROR' : 'INFO')));

        if (!isset($config['from'])) {
            throw new \Exception("'from' must be specified for mailer, should be a valid email-address.");
        }
        if (!isset($config['send_to'])) {
            throw new \Exception("'to' must be specified for mailer, should be a valid email-address or array of email-addresses.");
        }


        $message
            ->setFrom($config['from'])
            ->setTo($config['send_to'])
            ->setBody($msg)
        ;

        $result = $mailer->send($message);

    }
}