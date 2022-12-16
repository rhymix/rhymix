#!/bin/bash
# remove previous files folder
rm -rf $PWD/files

# set config
cp -f $PWD/.devcontainer/config.php $PWD/config/config.user.inc.php
cp -f $PWD/.devcontainer/install.php $PWD/config/install.config.php

# set domain to proxy one
cp -f $PWD/.devcontainer/config.php $PWD/config/config.user.inc.php

# create db (only first time)
service mysql start
mysql -uroot -proot -e "CREATE DATABASE rhymix CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -uroot -proot -e "CREATE USER 'rhymix'@localhost IDENTIFIED BY 'rhymix'"
mysql -uroot -proot -e "GRANT ALL ON rhymix.* to rhymix@localhost; FLUSH PRIVILEGES"