#!/bin/bash

# Based on https://github.com/nanasess/setup-php
sudo add-apt-repository -y ppa:ondrej/php

# Install all required packages
sudo apt -y install \
	php$1-apcu \
	php$1-bcmath \
	php$1-cli \
	php$1-common \
	php$1-curl \
	php$1-gd \
	php$1-intl \
	php$1-mbstring \
	php$1-mysql \
	php$1-readline \
	php$1-sqlite3 \
	php$1-xml \
	php$1-zip

# Adjust php.ini settings
sudo bash -c "echo 'register_argc_argv = On' >> /etc/php/$1/cli/php.ini"
sudo bash -c "echo 'opcache.enable = 1' >> /etc/php/$1/cli/conf.d/10-opcache.ini"
sudo bash -c "echo 'opcache.enable_cli = 1' >> /etc/php/$1/cli/conf.d/10-opcache.ini"
sudo bash -c "echo 'opcache.jit = tracing' >> /etc/php/$1/cli/conf.d/10-opcache.ini"
sudo bash -c "echo 'opcache.jit_buffer_size = 128M' >> /etc/php/$1/cli/conf.d/10-opcache.ini"

# Enable APCu
if [ -f "/etc/php/$1/cli/conf.d/20-apcu.ini" ]; then
  sudo bash -c "echo 'apc.enable_cli = 1' >> /etc/php/$1/cli/conf.d/20-apcu.ini"
fi

# Disable xdebug
sudo phpdismod -v ALL -s ALL xdebug

# Set and check default PHP version
sudo update-alternatives --set php /usr/bin/php$1
php -v
