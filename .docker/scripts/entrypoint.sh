#!/bin/bash
set -e

if [ -n "$JOOMLA_DB_PASSWORD_FILE" ] && [ -f "$JOOMLA_DB_PASSWORD_FILE" ]; then
  JOOMLA_DB_PASSWORD=$(cat "$JOOMLA_DB_PASSWORD_FILE")
fi

if [[ "$1" == apache2* ]] || [ "$1" == php-fpm ]; then
  uid="$(id -u)"
  gid="$(id -g)"
  if [ "$uid" = '0' ]; then
    case "$1" in
    apache2*)
      user="${APACHE_RUN_USER:-www-data}"
      group="${APACHE_RUN_GROUP:-www-data}"

      # strip off any '#' symbol ('#1000' is valid syntax for Apache)
      pound='#'
      user="${user#$pound}"
      group="${group#$pound}"

      # set user if not exist
      if ! id "$user" &>/dev/null; then
        # get the user name
        : "${USER_NAME:=www-data}"
        # change the user name
        [[ "$USER_NAME" != "www-data" ]] &&
          usermod -l "$USER_NAME" www-data &&
          groupmod -n "$USER_NAME" www-data
        # update the user ID
        groupmod -o -g "$user" "$USER_NAME"
        # update the user-group ID
        usermod -o -u "$group" "$USER_NAME"
      fi
      ;;
    *) # php-fpm
      user='www-data'
      group='www-data'
      ;;
    esac
  else
    user="$uid"
    group="$gid"
  fi

  if [ -n "$MYSQL_PORT_3306_TCP" ]; then
    if [ -z "$JOOMLA_DB_HOST" ]; then
      JOOMLA_DB_HOST='mysql'
    else
      echo >&2 "warning: both JOOMLA_DB_HOST and MYSQL_PORT_3306_TCP found"
      echo >&2 "  Connecting to JOOMLA_DB_HOST ($JOOMLA_DB_HOST)"
      echo >&2 "  instead of the linked mysql container"
    fi
  fi

  if [ -z "$JOOMLA_DB_HOST" ]; then
    echo >&2 "error: missing JOOMLA_DB_HOST and MYSQL_PORT_3306_TCP environment variables"
    echo >&2 "  Did you forget to --link some_mysql_container:mysql or set an external db"
    echo >&2 "  with -e JOOMLA_DB_HOST=hostname:port?"
    exit 1
  fi

  # If the DB user is 'root' then use the MySQL root password env var
  : "${JOOMLA_DB_USER:=root}"
  if [ "$JOOMLA_DB_USER" = 'root' ]; then
    : ${JOOMLA_DB_PASSWORD:=$MYSQL_ENV_MYSQL_ROOT_PASSWORD}
  fi
  : "${JOOMLA_DB_NAME:=joomla}"

  if [ -z "$JOOMLA_DB_PASSWORD" ] && [ "$JOOMLA_DB_PASSWORD_ALLOW_EMPTY" != 'yes' ]; then
    echo >&2 "error: missing required JOOMLA_DB_PASSWORD environment variable"
    echo >&2 "  Did you forget to -e JOOMLA_DB_PASSWORD=... ?"
    echo >&2
    echo >&2 "  (Also of interest might be JOOMLA_DB_USER and JOOMLA_DB_NAME.)"
    exit 1
  fi

  if [ ! -e index.php ] && [ ! -e libraries/src/Version.php ]; then
    # if the directory exists and Joomla doesn't appear to be installed AND the permissions of it are root:root, let's chown it (likely a Docker-created directory)
    if [ "$uid" = '0' ] && [ "$(stat -c '%u:%g' .)" = '0:0' ]; then
      chown "$user:$group" .
    fi

    echo >&2 "Joomla not found in $PWD - copying now..."
    if [ "$(ls -A)" ]; then
      echo >&2 "WARNING: $PWD is not empty - press Ctrl+C now if this is an error!"
      (
        set -x
        ls -A
        sleep 10
      )
    fi
    # use full commands
    # for clearer intent
    sourceTarArgs=(
      --create
      --file -
      --directory /usr/src/joomla
      --one-file-system
      --owner "$user" --group "$group"
    )
    targetTarArgs=(
      --extract
      --file -
    )
    if [ "$uid" != '0' ]; then
      # avoid "tar: .: Cannot utime: Operation not permitted" and "tar: .: Cannot change mode to rwxr-xr-x: Operation not permitted"
      targetTarArgs+=(--no-overwrite-dir)
    fi

    tar "${sourceTarArgs[@]}" . | tar "${targetTarArgs[@]}"

    if [ ! -e .htaccess ]; then
      # NOTE: The "Indexes" option is disabled in the php:apache base image so remove it as we enable .htaccess
      sed -r 's/^(Options -Indexes.*)$/#\1/' htaccess.txt >.htaccess
      chown "$user":"$group" .htaccess
    fi

    echo >&2 "Complete! Joomla has been successfully copied to $PWD"

    echo >&2 "Joomla installation in progress..."

    php installation/joomla.php install --site-name="$TCHOOZ_SITENAME" --admin-user="$TCHOOZ_SYSADMIN_LAST_NAME $TCHOOZ_SYSADMIN_FIRST_NAME" --admin-username="$TCHOOZ_SYSADMIN_USERNAME" --admin-password="$TCHOOZ_SYSADMIN_PASSWORD" --admin-email="$TCHOOZ_SYSADMIN_MAIL" --db-type=mysql --db-host="$JOOMLA_DB_HOST" --db-user="$JOOMLA_DB_USER" --db-pass="$JOOMLA_DB_PASSWORD" --db-name="$JOOMLA_DB_NAME" --db-prefix="jos_" -n
  fi

  if [ ! -e templates/g5_helium/templateDetails.xml ]; then
    echo >&2 "Copy of Gantry 5 Helium template in progress..."

    cp /templates/g5_helium/templateDetails.xml templates/g5_helium/templateDetails.xml
    cp -r /templates/g5_helium/custom/config templates/g5_helium/custom/
  fi

  if [ ! -e language/overrides/fr-FR.override.ini ]; then
    echo >&2 "Copy of language files in progress..."

    cp -r /language/overrides language/
  fi

  # Ensure the MySQL Database is created
  php /makedb.php "$JOOMLA_DB_HOST" "$JOOMLA_DB_USER" "$JOOMLA_DB_PASSWORD" "$JOOMLA_DB_NAME" "${JOOMLA_DB_TYPE:-mysqli}"

  echo >&2 "========================================================================"
  echo >&2
  echo >&2 "We prepare your Tchooz website for you !"
  echo >&2
  echo >&2 "========================================================================"


  # Fresh installation of Tchooz if configuration.php does not exist and installation folder exists
  if [ ! -e configuration.php ] && [ -d ".docker/installation/" ]; then
    if [ -f /core/logo.png ]; then
      echo >&2 "Copy of Tchooz logo in progress..."
      mv /core/logo.png images/custom/logo.png
    fi

    echo >&2 "Init configuration variables..."
    cp configuration.php.dist configuration.php
    cp htaccess.txt .htaccess

    echo >&2 "Configuring Joomla to use the database..."
    sed -i "s:\$host = '.*':\$host = '$JOOMLA_DB_HOST':g" configuration.php
    sed -i "s:\$user = '.*':\$user = '$JOOMLA_DB_USER':g" configuration.php
    sed -i "s:\$password = '.*':\$password = '$JOOMLA_DB_PASSWORD':g" configuration.php
    sed -i "s:\$db = '.*':\$db = '$JOOMLA_DB_NAME':g" configuration.php

    echo >&2 "Set permissions for configuration.php and .htaccess..."
    chown www-data: configuration.php

    echo >&2 "Reconfigure Joomla configuration..."
    php cli/joomla.php config:set sitename="$TCHOOZ_SITENAME" dbtype="mysqli" sef_rewrite=true frontediting=0
    php cli/joomla.php config:set offline="$TCHOOZ_OFFLINE" offline_message="$TCHOOZ_OFFLINE_MESSAGE" display_offline_message="$TCHOOZ_DISPLAY_OFFLINE_MESSAGE" offline_image="$TCHOOZ_OFFLINE_IMAGE" debug="$TCHOOZ_DEBUG" debug_lang="$TCHOOZ_DEBUG_LANG" live_site="$TCHOOZ_LIVE_SITE" secret="$TCHOOZ_SECRET" offset="$TCHOOZ_OFFSET" mailer="$TCHOOZ_MAILER" mailfrom="$TCHOOZ_MAIL_FROM" fromname="$TCHOOZ_MAIL_FROM_NAME" smtpauth="$TCHOOZ_MAIL_SMTP_AUTH" smtpuser="$TCHOOZ_MAIL_SMTP_USER" smtppass="$TCHOOZ_MAIL_SMTP_PASS" smtphost="$TCHOOZ_MAIL_SMTP_HOST" smtpsecure="$TCHOOZ_MAIL_SMTP_SECURITY" smtpport="$TCHOOZ_MAIL_SMTP_PORT" caching="$TCHOOZ_CACHING" cache_handler="$TCHOOZ_CACHE_HANDLER" cachetime="$TCHOOZ_CACHE_LIFETIME" session_handler="$TCHOOZ_SESSION_HANDLER"

    echo >&2 "Init database..."
    php cli/joomla.php database:import --folder=".docker/installation/vanilla" -n
    php cli/joomla.php tchooz:vanilla --action="import" --folder=".docker/installation/vanilla" -n
    php cli/joomla.php tchooz:vanilla --action="import_foreign_keys" --folder=".docker/installation/vanilla" -n
  fi

  echo >&2 "Configuring Joomla to use the database..."
  sed -i "s:\$host = '.*':\$host = '$JOOMLA_DB_HOST':g" configuration.php
  sed -i "s:\$user = '.*':\$user = '$JOOMLA_DB_USER':g" configuration.php
  sed -i "s:\$password = '.*':\$password = '$JOOMLA_DB_PASSWORD':g" configuration.php
  sed -i "s:\$db = '.*':\$db = '$JOOMLA_DB_NAME':g" configuration.php

  echo >&2 "Set permissions for configuration.php and .htaccess"
  chown www-data: configuration.php
  chown www-data: .htaccess

  echo >&2 "Reconfigure Joomla configuration..."
  php cli/joomla.php config:set sitename="$TCHOOZ_SITENAME" dbtype="mysqli" sef_rewrite=true frontediting=0
  php cli/joomla.php config:set offline="$TCHOOZ_OFFLINE" offline_message="$TCHOOZ_OFFLINE_MESSAGE" display_offline_message="$TCHOOZ_DISPLAY_OFFLINE_MESSAGE" offline_image="$TCHOOZ_OFFLINE_IMAGE" debug="$TCHOOZ_DEBUG" debug_lang="$TCHOOZ_DEBUG_LANG" live_site="$TCHOOZ_LIVE_SITE" secret="$TCHOOZ_SECRET" offset="$TCHOOZ_OFFSET" mailer="$TCHOOZ_MAILER" mailfrom="$TCHOOZ_MAIL_FROM" fromname="$TCHOOZ_MAIL_FROM_NAME" smtpauth="$TCHOOZ_MAIL_SMTP_AUTH" smtpuser="$TCHOOZ_MAIL_SMTP_USER" smtppass="$TCHOOZ_MAIL_SMTP_PASS" smtphost="$TCHOOZ_MAIL_SMTP_HOST" smtpsecure="$TCHOOZ_MAIL_SMTP_SECURITY" smtpport="$TCHOOZ_MAIL_SMTP_PORT" caching="$TCHOOZ_CACHING" cache_handler="$TCHOOZ_CACHE_HANDLER" cachetime="$TCHOOZ_CACHE_LIFETIME" session_handler="$TCHOOZ_SESSION_HANDLER"

  echo >&2 "Create super administrator user..."
  #php cli/joomla.php tchooz:user:add --username="sysadmin" --lastname="ADMINISTRATOR" --firstname="Emundus" --password="password" --email="dev@emundus.io" --usergroup="Registered,Super Users" --userprofiles="System administrator" --useremundusgroups="Administrateur de plateforme" -n
  php cli/joomla.php user:delete --username="$TCHOOZ_SYSADMIN_USERNAME" -n -q 2>/dev/null || true
  php cli/joomla.php tchooz:user:add --username="$TCHOOZ_SYSADMIN_USERNAME" --lastname="$TCHOOZ_SYSADMIN_LAST_NAME" --firstname="$TCHOOZ_SYSADMIN_FIRST_NAME" --password="$TCHOOZ_SYSADMIN_PASSWORD" --email="$TCHOOZ_SYSADMIN_MAIL" --usergroup="Registered,Super Users" --userprofiles="System administrator,Administrateur de plateforme,Formulaire de base" --useremundusgroups="Administrateur de plateforme" -n 2>/dev/null || true
  php cli/joomla.php user:reset-password --username="$TCHOOZ_SYSADMIN_USERNAME" --password="$TCHOOZ_SYSADMIN_PASSWORD" -n

  echo >&2 "Create coordinator user..."
  #php cli/joomla.php tchooz:user:add --username="coord@emundus.fr" --lastname="DEV" --firstname="Coordinator" --password="password" --email="coord@emundus.fr" --usergroup="Registered,Administrator" --userprofiles="Administrateur de plateforme,Formulaire de base" --useremundusgroups="Administrateur de plateforme" -n
  php cli/joomla.php user:delete --username="$TCHOOZ_COORD_USERNAME" -n -q 2>/dev/null || true
  php cli/joomla.php tchooz:user:add --username="$TCHOOZ_COORD_USERNAME" --lastname="$TCHOOZ_COORD_LAST_NAME" --firstname="$TCHOOZ_COORD_FIRST_NAME" --password="$TCHOOZ_COORD_PASSWORD" --email="$TCHOOZ_COORD_MAIL" --usergroup="Registered,Administrator" --userprofiles="Administrateur de plateforme,Formulaire de base" --useremundusgroups="Administrateur de plateforme" -n 2>/dev/null || true
  php cli/joomla.php user:reset-password --username="$TCHOOZ_COORD_USERNAME" --password="$TCHOOZ_COORD_PASSWORD" -n

  echo >&2 "Set Fabrik connection..."
  php cli/joomla.php tchooz:fabrik_connection_reset -n

  echo >&2 "Init standard email configuration..."
  php cli/joomla.php tchooz:config -n --keys="default_email_mailfrom,default_email_fromname,default_email_smtphost,default_email_smtpport,default_email_smtpsecure,default_email_smtpauth,default_email_smtpuser,default_email_smtppass,custom_email_conf" --values="$TCHOOZ_MAIL_FROM,$TCHOOZ_MAIL_FROM_NAME,$TCHOOZ_MAIL_SMTP_HOST,$TCHOOZ_MAIL_SMTP_PORT,$TCHOOZ_MAIL_SMTP_SECURITY,1,$TCHOOZ_MAIL_SMTP_USER,$TCHOOZ_MAIL_SMTP_PASS,0"

  echo >&2 "Run eMundus updates..."
  php cli/joomla.php tchooz:update -n --component=com_emundus,com_hikashop,com_fabrik,com_dropfiles
  php cli/joomla.php maintenance:database --fix

  echo >&2 "Purge cache..."
  php cli/joomla.php cache:clean

  echo >&2 "========================================================================"
  echo >&2
  echo >&2 "Awesome ! Your Tchooz website is ready !"
  echo >&2
  echo >&2 "Tchooz is now installed and ready to use : $TCHOOZ_LIVE_SITE"
  echo >&2
  echo >&2 "You can now access the local administration interface at: ${TCHOOZ_LIVE_SITE}administrator/?${TCHOOZ_ADMIN_ACCESS_TOKEN}"
  echo >&2 "Your local administrator account is : $TCHOOZ_SYSADMIN_USERNAME / $TCHOOZ_SYSADMIN_PASSWORD"
  echo >&2 "Your local coordinator account is : $TCHOOZ_COORD_USERNAME / $TCHOOZ_COORD_PASSWORD"
  echo >&2
  echo >&2 "========================================================================"
fi

exec "$@"