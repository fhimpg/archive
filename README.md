# Archive

Contact: archive@fhi.mpg.de

API Documentation: https://github.com/fhimpg/archive/wiki/API

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
## Step 4: Check and adapt settings in 'archive.conf'

- set name and base url
- add database settings
- add ldap if wanted
- define your document types and metadata

## Step 5: Adapt settings in php.ini

```
short_open_tag = On
max_execution_time = 300
max_input_time = 600
memory_limit = 1024Mpost_max_size = 10000M
file_uploads = On
upload_max_filesize = 10000M
max_file_uploads = 20
```

