# !/bin/bash
sudo a2enmod rewrite

echo '<VirtualHost *:80>
        ServerAdmin webmaster@localhost
        DocumentRoot /var/www/html
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
        <Directory "/var/www/html">
                AllowOverride All
        </Directory>
</VirtualHost>' | sudo tee /etc/apache2/sites-enabled/000-default.conf > /dev/null

sudo service apache2 restart
