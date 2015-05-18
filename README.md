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
- Detailed notifications sent via mail.


## Installing

### Requirements

- git: to install it easily, or just download the [zip file](https://github.com/peec/generic-linux-backup-scripts/archive/master.zip).
- php 5.4 or more.
- mysql: **if you  are going to use the mysql backup feature.**

```bash
cd ~
git clone https://github.com/peec/generic-linux-backup-scripts.git backup
chmod +x install.sh linuxbackups
./install.sh
# Help ?
./linuxbackups
# Help for filesystem backup ?
./linuxbackups backups:filesystem --help
# Help for mysql backup ?
./linuxbackups backups:mysql --help
```


## Getting started


### Configuration

Understanding the powerful configuration management is really important.

You can choose to run the backup script in different ways, you can configure the configuration inside the `config` folder
or you can use the various available arguments. See `linuxbackups backups:filesystem --help` for help.



### Database backup


Default configuration:

```json
{
    "backup_path": "/home/dropbox/Dropbox/Servers/%server_name/Databases",
    "amount_of_backups": 8,
    "database": {
        "user": "backup",
        "password": "backup",
        "host": "localhost",
        "ignore_databases": ["mysql", "information_schema", "performance_schema"]
    }
}
```


#### Restoring databasebackups:

```
cd /path/to/my/backups
gunzip the-file-you-want-to-restore.gz
mysql -u root -p DatabaseName < the-file-you-want-to-restore
```


### Filesystem backup

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

```bash
# m h  dom mon dow   command
00 00 * * * /home/dropbox/backup/linuxbackups backups:filesystem --backup-file-prefix="daily"
00 00 * * * /home/dropbox/backup/linuxbackups backups:mysql --backup-file-prefix="daily"
0 * * * *   /home/dropbox/backup/linuxbackups backups:mysql --backup-file-prefix="hourly" --setting.amount_of_backups=8
```


## Dropbox

See [Tutorial on Automatic Backups to Dropbox On Linux Distributions](http://pkj.no/en/automatic-backups-to-dropbox-on-linux/)


## Notifications

### Mail

Mail notifications will be more detailed, see config/config.yml.dist. If you use gmail transport, please enable IMAP on your gmail account.

### Pushover

Summed up notifications can be sent with pushover.

See config/config.yml.dist.





