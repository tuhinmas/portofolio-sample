#!/bin/bash

# Navigate to the application directory
cd /var/app/current/

# run pm2
if ! command -v npm &> /dev/null; then
    echo "npm not found, installing Node.js and npm..."
    # Install Node.js and npm using provided commands
    cd ~/
    sudo yum install https://rpm.nodesource.com/pub_16.x/nodistro/repo/nodesource-release-nodistro-1.noarch.rpm -y
    sudo yum install nodejs -y --setopt=nodesource-nodejs.module_hotfixes=1
else
    echo "npm is already installed."
fi
# Check if pm2 is installed
if ! command -v pm2 &> /dev/null; then
    echo "pm2 not found, installing PM2..."
    # Install PM2 using npm
    sudo npm install -g pm2
else
    echo "pm2 is already installed."
fi
# source /opt/elasticbeanstalk/containerfiles/envvars
cd /var/app/current
# pm2 kill

pm2 start worker/worker.yml
pm2 restart all