#!/bin/bash
# stop apache to update config
service nginx stop

# link repo to /var/www/ to access from web
ln -s /workspaces /var/www/

# set nginx config
cp -f $PWD/.devcontainer/nginx/web.conf /etc/nginx/sites-enabled/default
cp -f $PWD/common/manual/server_config/rhymix-nginx.conf /etc/nginx/snippets/rhymix.conf
sed -i "s/REPO_NAME/$RepositoryName/g" /etc/nginx/sites-enabled/default