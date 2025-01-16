FROM joomla:5-php8.2-apache
LABEL maintainer="Wilfried MAILLET <wilfried.maillet@emundus.fr>, EMUNDUS Development Team <support@emundus.fr>"

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# BUILDING VARS
ARG test_env=0

# Install nodejs, npm and yarn
RUN if [ "$test_env" = "1" ];then \
	curl -fsSL https://deb.nodesource.com/setup_18.x | bash -; \
	apt-get install -y nodejs; \
	npm install --global yarn; \
	else \
		echo "[BUILD INFO] : Jest is not required"; \
	fi

RUN apt-get update && apt-get install -y libz-dev \
	libmemcached-dev \
	libzip-dev \
	libmagickwand-dev \
	--no-install-recommends && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-enable memcached zip imagick

# Upgrade sed on 4.9 for bugfix with VirtioFS on MacOS (cf. https://forums.docker.com/t/sed-couldnt-open-temporary-file-xyz-permission-denied-when-using-virtiofs/125473/3)
RUN curl -o /tmp/libc6_2.36-8_$(dpkg --print-architecture).deb http://ftp.fr.debian.org/debian/pool/main/g/glibc/libc6_2.36-8_$(dpkg --print-architecture).deb; \
	dpkg -i /tmp/libc6_2.36-8_$(dpkg --print-architecture).deb; \
	rm /tmp/libc6_2.36-8_$(dpkg --print-architecture).deb

RUN curl -o /tmp/sed_4.9-1_$(dpkg --print-architecture).deb http://ftp.fr.debian.org/debian/pool/main/s/sed/sed_4.9-1_$(dpkg --print-architecture).deb; \
	dpkg -i /tmp/sed_4.9-1_$(dpkg --print-architecture).deb; \
	rm /tmp/sed_4.9-1_$(dpkg --print-architecture).deb

# install xdebug
RUN if [ "$test_env" = "1" ];then \
	pecl install xdebug; \
	docker-php-ext-enable xdebug; \
	{ \
		echo "xdebug.start_with_request=yes";  \
		echo "xdebug.client_host=127.0.0.1";  \
		echo "xdebug.mode=debug,profile,trace";  \
		echo "xdebug.mode=coverage";  \
		echo "xdebug.idekey=docker";  \
	} >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
	else \
		echo "[BUILD INFO] : Xdebug is not required"; \
	fi

# set recommended PHP.ini settings for opcache
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
echo 'opcache.memory_consumption=128'; \
echo 'opcache.interned_strings_buffer=8'; \
echo 'opcache.max_accelerated_files=4000'; \
echo 'opcache.revalidate_freq=2'; \
echo 'opcache.fast_shutdown=1'; \
echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Set PHP.ini settings for script execution and uploads
RUN { \
echo 'expose_php = Off'; \
echo 'file_uploads = On'; \
echo 'upload_max_filesize = 64M'; \
echo 'post_max_size = 64M'; \
echo 'memory_limit = 1024M'; \
echo 'max_execution_time = 600'; \
echo 'max_input_time = 600'; \
} > /usr/local/etc/php/php.ini

# Copy and set custom entrypoint script
COPY [".docker/scripts/entrypoint-build-ci-env.sh","/entrypoint.sh"]
COPY --chown=www-data:www-data  [".","/var/www/html"]

# Install yarn environment
RUN if [ "$test_env" = "1" ];then \
    cd /var/www/html/components; \
    yarn; \
	else \
		echo "[BUILD INFO] : Jest is not required"; \
	fi

# Entrypoint declaration
ENTRYPOINT [ "/entrypoint.sh"]

# Volume and workdir
VOLUME [ "/builds/emundus/cms/tchooz/" ]
WORKDIR /builds/emundus/cms/tchooz/

COPY [".docker/apache/000-default-build-ci-env.conf","/etc/apache2/sites-available/000-default.conf"]
COPY [".docker/apache/ports.conf","/etc/apache2/ports.conf"]

RUN if [ "$test_env" = "1" ];then \
		sed -i 's|/var/www/html|/builds/emundus/cms/tchooz|g' /etc/apache2/sites-available/000-default.conf; \
	else \
		echo "[BUILD INFO] : CI environment is not required"; \
	fi

CMD ["apache2-foreground"]

