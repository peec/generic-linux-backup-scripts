#!/bin/bash
if [ ! -f composer.phar ]; then
    curl -sS https://getcomposer.org/installer | php
fi

if [ ! -d vendor ]; then
    php composer.phar install
else
    php composer.phar update
fi


while true; do
    read -p "Do you wish to create a MySQL backup user? " yn
    case $yn in
        [Yy]* ) 
        PASSWORD=`date +%s | sha256sum | base64 | head -c 32 ; echo`
        echo "You will now be asked for the root password for MySQL:"
        cat setup/create_backup_user_mysql.sql | sed "s/IDENTIFIED BY  'backup'/IDENTIFIED BY  '${PASSWORD}'/g" | mysql -u root -p;
        echo  "We created/tried to create backup@localhost with password ${PASSWORD}, please configure config/database.json."
        break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done

