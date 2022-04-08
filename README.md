# Archive

Contact: archive@fhi.mpg.de


# Installation


## Prerequisites:

- Debian/Ubuntu 
- Apache/Nginx
- Mariadb/Mysql
- PHP (including MySQL module for PHP)
- Imagemagick
- nullmailer
- Dropzone (https://github.com/dropzone/dropzone)
- Json Viewer (https://github.com/abodelot/jquery.json-viewer)

## Step 1: Build your basic filesystem

```
# mkdir /a /a/etc /a/bin /a/data /a/cache /a/tmp /a/log
# chmod 777 /a/data /a/cache /a/tmp /a/log

# git clone https://github.com/fhimpg/archive.git
# cp archive/src/* /a/www
# cp archive/archive.conf /a/www
# cp archive/support/dropzone.* /a/www
# cp -r archive/support/json-viewer /a/www
# cp archive/archive.rewrite.conf /a/etc
```

## Step 2: Build your database

- install database (mariadb or MySQL)
- add a database user
- create a empty database 
- import database schema:
```
# mysql archive < archive.sql
```

## Step 3: Configure a virtual host in your webserver, eg:

```
<VirtualHost *:443>
  ServerName archive.my.domain:443

  DocumentRoot /a/www

  <Directory /a/www>
    AllowOverride All
  </Directory>

  SSLEngine on
  SSLCertificateFile /etc/acme.sh/my.domain/fullchain.cer
  SSLCertificateKeyFile /etc/acme.sh/my.domain/my.domain.key

  RewriteEngine On
  Include /a/etc/archive.rewrite.conf

</VirtualHost>
```