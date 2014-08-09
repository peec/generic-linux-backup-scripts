# Generic Backup Scripts For Linux

This tool can create backups of folders and mysql databases. It's simple yet super powerful.

## Can backup:

- Filesystem
- MySQL databases

## Features:

- Easy to understand and use.
- Rotating backups, select how many backups you want stored for a specific item. Can also be disabled.
- Made to be easy to setup, and use for multiple servers.



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

You can choose to run the backup script in different ways, you can configure the configuration inside the `config` folder
or you can use the various available arguments. See `linuxbackups backups:filesystem --help` for help.





## Automated backups

This script runs daily backups of **filesystem** and **databases**, it also runs hourly backup of dbs where we store 8
backups of the hourly onces.

```bash
# m h  dom mon dow   command
00 00 * * * /home/dropbox/backup/linuxbackups backups:filesystem --backup-file-prefix="daily"
00 00 * * * /home/dropbox/backup/linuxbackups backups:mysql --backup-file-prefix="daily"
0 * * * *   /home/dropbox/backup/linuxbackups backups:mysql --backup-file-prefix="hourly" --setting.amount_of_backups=8
```