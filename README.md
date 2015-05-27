# Generic Backup Scripts For Linux

This tool can create backups of folders and mysql databases. It's simple yet super powerful.

## Can backup:

- Filesystem
- MySQL databases

## Features:

- Made to be easy to setup, and use for multiple servers.
- Easy to understand and use.
- Rotating backups, select how many backups you want stored for a specific item. Can also be disabled.
- Log files for the backup, you actually know what was done.
- Notifications with [pushover](http://pushover.net) when something goes wrong, and possible also to send per successful backup routine.
- Detailed notifications sent via mail when a backup is done (or failed).


## Requirements

- git: to install it easily, or just download the [zip file](https://github.com/peec/generic-linux-backup-scripts/archive/master.zip).
- php 5.4 or more.
- mysql: **if you  are going to use the mysql backup feature.**

##### Debian based / Ubuntu, requirements 

```
sudo apt-get install php5 git
```


## Installing



```bash
git clone https://github.com/peec/generic-linux-backup-scripts.git /opt/backup-tool
cd /opt/backup-tool
chmod +x install.sh linuxbackups
./install.sh
# Help ?
./linuxbackups
# Help for filesystem backup ?
./linuxbackups backups:filesystem --help
# Help for mysql backup ?
./linuxbackups backups:mysql --help
```

- The install script will ask you if you want to create a database backup user. It generates a backup user with a random password. Take care of this password as you will need it to configure the database backup user.


# Getting started



## Creating database backups

**Our goal: You want to create daily backups of all your databases, keep 8 backups for the past 8 days.**

1. Lets create a directory, where we want to store all our database backups. Lets store them in `/opt/backups`. /opt/backups folder could be a mounted drive of some kind. But for now we will use a directory on the same server.

    ```
    sudo mkdir /opt/backups
    ```
1. Configure database connection details, put this in `/opt/backup-tool/config/database.json`

    ```
    {
        "backup_path": "/opt/backups/Mysql/Databases",
        "amount_of_backups": 10,
        "database": {
            "user": "backup",
            "password": "PASSWORD FOR BACKUP DB USER",
            "host": "localhost",
            "ignore_databases": ["mysql", "information_schema", "performance_schema"]
        }
    }
    ```
    
    - **backup_path**: this is where we want to store our database backups. You can include `%server_name` in this path if you want e.g. `/opt/backups/%server_name/Mysql/Databases` if you have multiple servers connected to that folder. 
    - **amount_of_backups**: the amount of backups we want to keep. 
    - **database**:
        - **user**: The username of your backup user. You can also use root, but creating a backup user is recommended.
        - **password**: The password for the backup user.
        - **host**: The hostname of the mysql db.
        - **ignore_databases**: Ignores the array of databases, you should ignore the built in databases such as `mysql`, `performance_schema`, `information_schema`. You can also ignore other databases if you want to exclude them.
    
1. Lets test that our configuration works:

    ```
    /opt/backup/linuxbackups backups:mysql --backup-file-prefix="daily" --notifications-when-done
    # Are there any backups?
    ls -al /opt/backups/Mysql/Databases
    ```
    - The `--notifications-when-done` will send notification to email or pusher when the backup is done. If this flag is not present it will only send notifications if the backup routine failed. **Note that notifications are only sent if you configured `config/config.yml`**
    - The `--backup-file-prefix="daily"` creates a namespace for the current backup routine. This is useful if you also want for example `hourly` backups. 
    
1. Automate the daily backup routine, we will do this with cron:
    ```
    sudo EDITOR=nano crontab -e
    ```
    and now add this line, which will run daily backup routine every day at midnight.
    ```
    00 00 * * * /opt/backup-tool/linuxbackups backups:mysql --backup-file-prefix="daily" --notifications-when-done
    ```
    
1. Lets configure so that the backup routine emails us the notification reports:
    ```
    sudo nano /opt/backup-tool/config/config.yml
    ```
    And configure the mailer
    ```
    # EMAIL NOTIFICATIONS:
    # To enable email notifications, uncomment below:
    mailer:
        send_to: mygmail@gmail.com
        transport: gmail
        gmail:
            username: mygmail@gmail.com
            password: Mygmailpassword
    ```
    




##### Restoring databasebackups:

```
cd /path/to/my/backups
gunzip the-file-you-want-to-restore.gz
mysql -u root -p DataBaseThatShouldGetTheImportedSQL < the-file-you-want-to-restore
```


## Filesystem backup

Default configuration:

```json
{
    "backup_path": "/home/dropbox/Dropbox/Servers/%server_name/Sites",
    "amount_of_backups": 8,
    "directories": {
        "somesite": ["/home/www-data/vhosts/no/somesite/images", "/and/another/dir/related/to/somesite"],
        "someothersite": ["/home/...."]
    }
}
```


## Automated backups

This script runs daily backups of **filesystem** and **databases**, it also runs hourly backup of dbs where we store 8
backups of the hourly onces.


```
crontab -e
```

```bash
# m h  dom mon dow   command
00 00 * * * /home/dropbox/backup/linuxbackups backups:filesystem --backup-file-prefix="daily"
00 00 * * * /home/dropbox/backup/linuxbackups backups:mysql --backup-file-prefix="daily"
0 * * * *   /home/dropbox/backup/linuxbackups backups:mysql --backup-file-prefix="hourly" --setting.amount_of_backups=8
```


## Dropbox

See [Tutorial on Automatic Backups to Dropbox On Linux Distributions](http://pkj.no/en/automatic-backups-to-dropbox-on-linux/)


## Notifications

You must configure `/opt/backup-tool/config/config.yml` so we know where to send emails / pushover.


```
Use --notifications-when-done if you want to send info notifiers aswell.
``` 

#### Mail

Mail notifications will be more detailed, see config/config.yml.dist. If you use gmail transport, please enable IMAP on your gmail account.

#### Pushover

Summed up notifications can be sent with pushover.

See config/config.yml.dist.





