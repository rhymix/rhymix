#!/bin/bash

AUTH="-uroot -proot"

# Start MySQL
sudo systemctl start mysql.service

# Create default database
sudo mysql $AUTH -e "CREATE DATABASE rhymix CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci"
sudo mysql $AUTH -e "CREATE USER rhymix@localhost IDENTIFIED WITH mysql_native_password BY 'rhymix'"
sudo mysql $AUTH -e "GRANT ALL ON rhymix.* to rhymix@localhost; FLUSH PRIVILEGES"

# Check MySQL version
sudo mysql $AUTH -e "SELECT VERSION()"
