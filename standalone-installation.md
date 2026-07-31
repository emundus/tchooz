# Standalone installation

## Server recommended prerequisites
- 4 cores (8 vCPU)
- 8 GB of RAM minimum, 16 GB if possible
- 50 GB of storage minimum is recommended, but this depends on your usage. We recommend setting up alerts so you can monitor how your storage grows over time.


## Installing the web server: Apache2
For the Tchooz web application to work properly, which is based on PHP and operates via the HTTP/HTTPS protocols, it is essential to install a web server. You can use Apache or Nginx.

### Step 1 System update
Before starting the installation, we need to update the repositories and the packages already installed. Run the following command:
```bash
sudo apt update && apt -y upgrade
```

### Step 2: Installing Apache2
We are now going to install Apache2 and its required dependencies:
```bash
sudo apt install -y apache2 libapache2-mod-security2 apt-transport-https ca-certificates curl libxml2-dev python3-apt
```

### Step 3: Activating the expires module
It is also important to activate the Apache expires module to optimise resource management. Run the following commands:
```bash
sudo a2enmod expires
sudo systemctl restart apache2
```

### Step 4: Configuring automatic start-up
To ensure that Apache2 starts automatically when the system is rebooted, run :
```bash
sudo systemctl enable apache2
```

### Step 5: Installing PHP
The Tchooz platform requires PHP to run, but this is not installed by default with Apache2. PHP version 8.2 is currently recommended for the Tchooz software.
To install PHP and its extensions, you must first add the Sury package repository and trust its :
```bash
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
```

### Step 6: Configuring the PHP repository
> From now on, all the values you need to change will be enclosed in double curly braces:
> {{ exemple }} 
> Please reread each line carefully before using them.

```bash
sudo echo "deb https://packages.sury.org/php/ {{ os_name_version }} main" >> /etc/apt/sources.list.d/php.list
sudo apt-get update
```
> `os_name_version` : Name of the Debian version you are using. Debian 12 is strongly recommended (bookworm).


### Step 7: Installing PHP extensions
The following PHP extensions are required for Tchooz to function correctly:
- `gd` 
- `cli` 
- `common` 
- `mbstring` 
- `bcmath` 
- `ldap` 
- `libapache2-mod-php8.2`
- `pdo` 
- `mysql` 
- `zip` 
- `xml` 
- `curl` 
- `intl` 
- `redis` 
- `xmlrpc` 
- `gmp` 
- `bz2` 
- `apcu`

To install these extensions, run the following command:
```bash
sudo apt install -y php8.2 php8.2-cli php8.2-pdo php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-ldap php8.2-intl php8.2-redis php8.2-xmlrpc php8.2-gmp php8.2-bz2 php8.2-apcu libapache2-mod-php8.2
```

### Step 8: Configuration de PHP
You then need to set certain PHP configuration parameters:
- Increase the maximum size of uploaded files in /etc/php/8.2/apache2/php.ini:
    ```bash
    upload_max_filesize = 20M
    ```
- Adjust the Apache configuration parameters according to your number of CPU cores in the /etc/apache2/mods-available/mpm_prefork.conf file:  
    Pour un système avec moins de 4 cœurs :
    ```bash
      StartServers				5
      MinSpareServers				5
      MaxSpareServers				10
      ServerLimit					150
      MaxRequestWorkers			150
      MaxConnectionsPerChild		0
    ```

    Pour un système avec 4 à 8 cœurs :
    ```bash
    StartServers				40
    MinSpareServers				50
    MaxSpareServers				100
    ServerLimit					500
    MaxRequestWorkers			500
    MaxConnectionsPerChild		750
    ```

    Pour un système avec plus de 8 cœurs :
    ```bash
    Pour un système avec plus de 8 cœurs :
    StartServers				80
    MinSpareServers				100
    MaxSpareServers				200
    ServerLimit					1000
    MaxRequestWorkers			1000
    MaxConnectionsPerChild		1500
    ```
  
### Step 9: Restarting Apache
After making these changes, you need to restart Apache2:
```bash
sudo systemctl restart apache2
```

## Installing third-party components
To ensure the proper functioning of certain Tchooz modules, it is necessary to install additional packages. The EMUNDUS support team may need these packages during the :
- `python3`
- `python3-mysqldb`
- `python3-pip`
In a Debian environment, these packages are usually available in the official repositories. To install them, run the following commands:
```bash
sudo apt update && sudo apt install -y python3 python3-mysqldb python3-pip python3-pymysql
```

## DBMS installation: MySQL
For the platform to function properly, a database management system (DBMS) is required. We will use MySQL as the database for our application platform.

### Installing MySQL
Tchooz need MySQL 8.4 LTS.

## Installing the Tchooz application components
After installing the Apache2 web server, PHP and MySQL, we're ready to pick up the Tchooz application components.

### Step 1: Creating the project file
Start by creating a directory to host the platform. By convention, the default folder for the web server is `/var/www/`, but in this guide we will be using a partition mounted on `/mnt/data/`, so the web project will be in `/mnt/data/web/{{ project }}`.

Navigate to the desired directory, then create the folder for your web project :
```bash
mkdir -p /mnt/data/web/
cd /mnt/data/web/
```

### Step 2: Recovering application components
The eMundus application components must be retrieved using the Git protocol, as the source code can be found on Github. 
- Check that Git is installed:
    ```bash
    sudo apt install git
    ```
- Clone the repository:
    ```bash
    git clone https://github.com/emundus/tchooz.git
    ```
- Move to the project directory:
    ```bash
    cd /mnt/data/web/tchooz
    ```
  
### Step 3: File configuration
Once you have retrieved the source code, you need to create certain directories and copy the necessary files:
```bash
mkdir language/overrides
cp -r .docker/installation/language/overrides/* language/overrides/
cp -r .docker/installation/templates/g5_helium/* templates/g5_helium/
cp .docker/installation/logo.png images/custom/logo.png
```

### Step 4: Configuring permissions
It is also important to configure user and group permissions for the web server on the platform directory. Apache2 uses the default user www-data :
```bash
sudo chown -R www-data:www-data {{ project_path }}/
```
> Make sure you replace {{ project_path }} with your project path (example: /mnt/data/web/tchooz/).

# Configuration and implementation of the Tchooz application
## Web server configuration
Now it's time to configure Apache2 to run the application platform.

The configuration is done using a VirtualHost, which is used to direct the web server to the right path for your files while managing the server's behaviour.

### Step 1: Creating the VirtualHost
To set up VirtualHost, open a configuration file by running the following command:
```bash
sudo nano /etc/apache2/sites-available/{{ sitename }}.conf
```
> Replace {{ site_server_name }} with the name of your platform (example: app.example.fr).
> Replace {{ site_server_path }} with the path of your project (example: mnt/data/web/{{ project }})

### Step 2: File configuration
In this file, add the following lines:
```apache
<VirtualHost *:80>
    ServerName {{ site_server_name }}
    DocumentRoot {{ site_server_path }}
    <Directory />
        AllowOverride All
    </Directory>
    <Directory {{ site_server_path }}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride all
        Require all granted
    </Directory>
    ErrorLog /var/log/apache2/{{ site_server_name }}-error.log
    LogLevel error
    CustomLog /var/log/apache2/{{ site_server_name }}-access.log combined
    RewriteEngine on
    RewriteCond %{SERVER_NAME} ={{ site_server_name }}
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,NE,R=permanent]
</VirtualHost>

<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName {{ site_server_name }}
    DocumentRoot {{ site_server_path }}
    <Directory />
        AllowOverride All
    </Directory>
    <Directory {{ site_server_path }}>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride all
        Require all granted
    </Directory>
    ErrorLog /var/log/apache2/{{ site_server_name }}-error.log
    LogLevel error
    CustomLog /var/log/apache2/{{ site_server_name }}-access.log combined
</VirtualHost>
</IfModule>
```

> At this stage, the Apache2 web server is configured.
> Now generate your SSL certificate and add it to your vhost.

### Step 3: Activating the configuration
Now it's time to activate our configuration by executing the following commands:
```bash
sudo a2ensite {{ sitename }}.conf
sudo systemctl reload apache2.service
```
> Make sure you replace {{ sitename }} with the name of your platform.

### Step 4: Activate the necessary modules
To finalise the configuration, activate the two Apache modules required and restart the :
```bash
sudo a2enmod rewrite
sudo a2enmod ssl
sudo systemctl restart apache2
```
We're now going to look at the second essential element, which concerns the database.

## Setting up the database

### Step 1: Create database
In MySQL CLI :
```sh
CREATE DATABASE {{ site_db }};
CREATE USER '{{ db_user }}'@'{{ db_host }}' IDENTIFIED BY '{{ db_password }}';
GRANT ALL PRIVILEGES ON {{ site_db }}.* TO '{{ db_user }}'@'{{ db_host }}';
GRANT PROCESS ON *.* TO '{{ db_user }}'@'{{ db_host }}';
FLUSH PRIVILEGES;
```

Here a basic MySQL configuration for Tchooz : 
```
[mysqld]
max_allowed_packet = 256M
innodb_log_file_size = 1024M
innodb_log_buffer_size = 80M
innodb_default_row_format = DYNAMIC
innodb_strict_mode = 0
sql_mode = ""
disable_log_bin
```

Pour finaliser l'installation vous devez maintenant copier le fichier configuration.php.dist ainsi que le .htaccess
```sh
cp configuration.php.dist configuration.php
cp htaccess.txt .htaccess
chown www-data: configuration.php
chown www-data: .htaccess
```

Remplacer dans le configuration.php les valeurs lié à votre base de données crée précédemment
```sh
sed -i "s:\$host = '.*':\$host = '{{ db_host }}':g" configuration.php
sed -i "s:\$user = '.*':\$user = '{{ db_user }}':g" configuration.php
sed -i "s:\$password = '.*':\$password = '{{ db_password }}':g" configuration.php
sed -i "s:\$db = '.*':\$db = '{{ site_db }}':g" configuration.php
```

Initialisez la db avec les commande suisvantes:

```shell
php cli/joomla.php database:import --folder=".docker/installation/vanilla" -n

php cli/joomla.php tchooz:vanilla --action="import" --folder=".docker/installation/vanilla" -n

php cli/joomla.php tchooz:vanilla --action="import_foreign_keys" --folder=".docker/installation/vanilla" -n

php cli/joomla.php tchooz:language --job=database -n
```

### Create an administrator user

```shell
php cli/joomla.php tchooz:user:add --username="{{admin_user}}" --lastname="{{admin_lastname}}" --firstname="{{admin_firstname}}" --password="{{admin_password}}" --email="{{admin_email}}" --usergroup="Registered,Super Users" --userprofiles="System administrator,Administrateur de plateforme,Formulaire de base" --useremundusgroups="Administrateur de plateforme" -n 2>/dev/null || true
```

## Project update

```shell

# to set platform on mode maintenance 
sudo php cli/joomla.php site:down

sudo git reset --hard

# Git project update
sudo git fetch --prune
sudo git pull

# Platform CLI update
sudo php cli/joomla.php tchooz:update  -n --component=com_emundus,com_hikashop,com_fabrik,com_dropfiles
sudo php cli/joomla.php maintenance:database --fix

# re open platform to the world
sudo php cli/joomla.php site:up

```
