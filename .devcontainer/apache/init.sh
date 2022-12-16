#!/bin/bash
# stop apache to update config
service apache2 stop

# link repo to /var/www/ to access from web
ln -s /workspaces /var/www/

# set apache2 config
cp -f $PWD/.devcontainer/apache/web.conf /etc/apache2/sites-enabled/000-default.conf
sed -i "s/REPO_NAME/$RepositoryName/g" /etc/apache2/sites-enabled/000-default.conf

# enable rewrite
a2enmod rewrite