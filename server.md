# Assumed CentOS 6.8

# Install repos (IUS for PHP recommended by Magento) [root]
rpm -Uvh https://dl.fedoraproject.org/pub/epel/epel-release-latest-6.noarch.rpm
rpm -Uvh https://centos6.iuscommunity.org/ius-release.rpm

# Install, start MySQL and secure installation (store password securely) [root]
yum install mysql-server
/etc/init.d/mysqld start
/usr/bin/mysql_secure_installation

# Install and start nginx [root]
yum install nginx

# Install PHP [root]
# php56u package not required as not running via apache module
yum install php56u-opcache php56u-xml php56u-mcrypt php56u-gd php56u-mysql php56u-intl php56u-mbstring php56u-bcmath php56u-pdo php56u-fpm php56u-common php56u-cli

# Configure PHP [root]
vi /etc/php.ini
```
cgi.fix_pathinfo = 0
memory_limit = 500M
```

# Create ipshark user and group [root]
useradd ipshark
chmod 755 /home/ipshark

# Create project directory [ipshark]
su ipshark
mkdir ~/code

# Configure nginx [root]
vi /etc/nginx/nginx.conf
```
user ipshark;
worker_processes 4;
```

# Chown nginx temp directory [root]
chown -R ipshark:ipshark /var/lib/nginx

# Add nginx virtual host [root]
vi /etc/nginx/conf.d/ipshark.conf
```
server {
    listen 80;
    server_name ipshark.co;
    root /home/ipshark/code/public;

    index index.html index.htm index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    access_log off;
    error_log  /var/log/nginx/ipshark-error.log error;

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

# Start nginx [root]
/etc/init.d/nginx start

# Update FPM user/group configuration [root]
vi /etc/php-fpm.d/www.conf
```
user = ipshark
group = ipshark
```

# Start FPM [root]
/etc/init.d/php-fpm start

# Install and start Redis [root]
yum install redis
/etc/init.d/redis start

# Create MySQL user and database [root]
mysql -uroot -p
CREATE DATABASE ipshark;
CREATE USER 'ipshark'@'localhost' IDENTIFIED BY 'TBjtAxXXVpfAHMA9gt8LWWTnoaBBGx';
GRANT ALL PRIVILEGES ON ipshark.* TO 'ipshark'@'localhost';
FLUSH PRIVILEGES;
exit

# Install git [root]
yum install git

# Install Node [root]
cd /usr/src
wget https://nodejs.org/download/release/v4.4.5/node-v4.4.5-linux-x64.tar.gz
cd /usr/local
tar --strip-components 1 -xzf /usr/src/node-v4.4.5-linux-x64.tar.gz
node -v

# Install composer [ipshark]
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer

# Enable auto-start for all services [root]
chkconfig --levels 235 mysqld on
chkconfig --levels 235 nginx on
chkconfig --levels 235 php-fpm on
chkconfig --levels 235 redis on

# Generate SSH keys [ipshark]
ssh-keygen -t rsa -b 4096

# Add public key to github account [ipshark]
cat ~/.ssh/id_rsa.pub

# Clone repository [ipshark]
cd ~/code
git clone git@github.com:opmonk/dan-laravel.git .

# Change database connection details and BUGSNAG_API_KEY in .env.example [ipshark]
vi .env.example

# Run setup process [ipshark]
./install.sh

# Revert .env.example back [ipshark]
git checkout -- .

# Install supervisor [root]
yum install python-setuptools
easy_install supervisor
echo_supervisord_conf > /etc/supervisord.conf
mkdir /etc/supervisord.d

vi /etc/supervisord.conf
```
[include]
files = /etc/supervisord.d/*.conf
```

vi /etc/rc.d/init.d/supervisord
```
#!/bin/bash
#
# Startup script for the Supervisor server
#
# Tested with CentOS release 6.6
#
# chkconfig: 2345 85 15
# description: Supervisor is a client/server system that allows its users to \
#              monitor and control a number of processes on UNIX-like \
#              operating systems.
#
# processname: supervisord
# pidfile: /var/run/supervisord.pid

# Source function library.
. /etc/rc.d/init.d/functions

RETVAL=0
prog="supervisord"
SUPERVISORD=/usr/bin/supervisord
PID_FILE=/var/run/supervisord.pid
CONFIG_FILE=/etc/supervisord.conf

start()
{
        echo -n $"Starting $prog: "
        $SUPERVISORD -c $CONFIG_FILE --pidfile $PID_FILE && success || failure
        RETVAL=$?
        echo
        return $RETVAL
}

stop()
{
        echo -n $"Stopping $prog: "
        killproc -p $PID_FILE -d 10 $SUPERVISORD
        RETVAL=$?
        echo
}

reload()
{
        echo -n $"Reloading $prog: "
        if [ -n "`pidfileofproc $SUPERVISORD`" ] ; then
            killproc $SUPERVISORD -HUP
        else
            # Fails if the pid file does not exist BEFORE the reload
            failure $"Reloading $prog"
        fi
        sleep 1
        if [ ! -e $PID_FILE ] ; then
            # Fails if the pid file does not exist AFTER the reload
            failure $"Reloading $prog"
        fi
        RETVAL=$?
        echo
}

case "$1" in
        start)
                start
                ;;
        stop)
                stop
                ;;
        restart)
                stop
                start
                ;;
        reload)
                reload
                ;;
        status)
                status -p $PID_FILE $SUPERVISORD
                RETVAL=$?
                ;;
        *)
                echo $"Usage: $0 {start|stop|restart|reload|status}"
                RETVAL=1
esac
exit $RETVAL
```

chmod +x /etc/rc.d/init.d/supervisord
chkconfig --add supervisord
chkconfig supervisord on
service supervisord start




# Digital Ocean MySQL passwords
root: QDkBcofaAHQuuJof4VNnyZFBETm2Nj
ipshark: TBjtAxXXVpfAHMA9gt8LWWTnoaBBGx
