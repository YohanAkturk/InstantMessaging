# Security project 4

## About this project ##

This project is a simple private chat application with security features behind.

## Language used ##
    -   PHP
    -   JavaScript + Vue.js

## Requirement ##

To set up this project, you will need :
- Composer
- npm
- Xampp

## Set up xampp ##

Now we need to set up Xampp to use our own self-signed certificated. 
First you need to indicate the root directory of the project. For this you will need to change in the httpd.conf file the document root.
In the directory of Xampp, enter this commande to go to the correct directory :
```
cd apache/conf
```

In this directory, just modify the httpd.conf file with a text editor. In the file change the document root into
the public directory of the project.<br>
In the same directory, go in the ssl.key directory and execute this command :
```
openssl req -new -x509 -nodes -newkey rsa:4096 -keyout server.key -out server.crt -days 825
```

And change the permission of both files :
```
chmod 400 server.key
chmod 444 server.crt
```
Move the certificate in the ssl.crt directory.<br>

Next go back in the httpd.conf file and uncomment those 3 lines :
```
LoadModule ssl_module modules/mod_ssl.so
LoadModule socache_shmcb_module modules/mod_socache_shmcb.so
Include conf/extra/httpd-ssl.conf
```

Next go inside the extra directory and modify the httpd-vhosts.conf to add a new virtual host like the following example :
```
<VirtualHost *:80 *:443>
    ServerAdmin webmaster@dummy-host2.example.com
    DocumentRoot "THE_PATH_TO_THE_PUBLIC_DIRECTORY_OF_THE_PROJECT"
    ServerName YOUR_SERVER_NAME
    ErrorLog "logs/dummy-host2.example.com-error.log"
    CustomLog "logs/dummy-host2.example.com-access.log" common
	SSLEngine on
	SSLCertificateFile "PATH_TO_YOUR_CERTIFICATE"
	SSLCertificateKeyFile "PATH_TO_THE_SERVER_KEY"
</VirtualHost>
```

## Set up the project ##

### Set up database ###

First, you need to create the data base in Xampp with phpmyadmin. 
Once it create, you can copy and execute the queries in the sql file (dbcreation.sql) given in this repository to create all the necessaries tables.

### Set up project file ###

First create a copy of the .env.example file in laravel-chat directory :

```
cp .env.example .env
```

Then in the created .env file, you will need to change some parameter :
- Set the APP_DEBUG to false (APP_DEBUG=false)
- Put the name of your data base at the DB_NAME parameter (DB_DATABASE=YourDatabaseName) and set the DB_USERNAME (DB_USERNAME=YourUsername) and DB_PASSWORD (DB_PASSWORD=YouPassword) with your data base login and password
- Set BROADCAST_DRIVER to pusher (BROADCAST_DRIVER=pusher)
- Finally set the pusher parameter. For this you can use my parameter if you want or generate a new key in the pusher API.
    
My pusher parameter (you can copy it) : 
- PUSHER_APP_ID=1412122
- PUSHER_APP_KEY=979e5ce5e71a9278d32c
- PUSHER_APP_SECRET=9e39c5c3a50d5e0522e1
- PUSHER_APP_CLUSTER=eu

### Set up the project ###

To set finaly set up the project, you need to execute the following command in the laravel-chat directory :

```
composer install
npm install
php artisan key:generate
npm run dev
```
If you see a warning when it's compiling, it's normal because the build notification are disabled.<br>

Then you can lauch Xampp and enter https://localhost in the url.

On the home page you will see 2 buttons on the top right to log or to register.

## Contributors ##

- Leong Paeg-Hing 56133
- Arturk Yohan 56514




