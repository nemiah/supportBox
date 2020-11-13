# supportBox

Raspbian Buster Installation:

sudo apt update
sudo apt dist-upgrade
sudo apt -y install mariadb-server-10.3 supervisor autossh telnet nmap unzip zip mutt sudo rsync curl ntp git libapache2-mod-php7.3 php7.3-mysql php7.3-soap php7.3-gd php7.3-imap php7.3-mbstring php7.3-xml php7.3-curl php7.3-tidy php7.3-zip php7.3-mailparse phpmyadmin;

sudo rm /var/www/html/index.html

password=`date +%s | sha256sum | base64 | head -c 20`

sudo mysql -uroot -e "CREATE USER 'supportbox'@'localhost' IDENTIFIED BY '$password';
GRANT USAGE ON * . * TO 'supportbox'@'localhost' IDENTIFIED BY '$password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;
CREATE DATABASE IF NOT EXISTS supportbox;
GRANT ALL PRIVILEGES ON supportbox . * TO 'supportbox'@'localhost';"

git clone https://github.com/nemiah/supportBox.git /var/www/html/supportBox

chmod 777 /var/www/html/supportBox/system/DBData/Installation.pfdb.php
echo "<?php echo \"This is a database-file.\"; /*
host&%%%&user&%%%&password&%%%&datab&%%%&httpHost
varchar(30)&%%%&varchar(20)&%%%&varchar(20)&%%%&varchar(20)&%%%&varchar(40)
localhost                     &%%%&supportbox          &%%%&$password&%%%&supportbox          &%%%&*                                       %%&&&
*/ ?>
" > /var/www/html/supportBox/system/DBData/Installation.pfdb.php;

sudo ln -s /var/www/html/supportBox/supportBox/SBInfo/server/supportbox.conf /etc/supervisor/conf.d/supportbox.conf
sudo cp /var/www/html/supportBox/supportBox/SBInfo/server/sudoers /etc/sudoers.d/010_supportbox
