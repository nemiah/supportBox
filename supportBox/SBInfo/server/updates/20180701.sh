# !/bin/bash
a2enmod rewrite

echo '<VirtualHost *:80>
        ServerAdmin webmaster@localhost
        DocumentRoot /var/www/html
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
        <Directory "/var/www/html">
                AllowOverride All
        </Directory>
</VirtualHost>' | tee /etc/apache2/sites-enabled/000-default.conf > /dev/null

service apache2 restart
