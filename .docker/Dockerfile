FROM joomla:5.3.0-php8.2-apache

# Install required packages, PHP extensions
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libz-dev \
        libmemcached-dev \
        libzip-dev \
        libmagickwand-dev && \
    rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install zip && \
    docker-php-ext-enable memcached imagick

# Upgrade sed on 4.9 for bugfix with VirtioFS on MacOS (cf. https://forums.docker.com/t/sed-couldnt-open-temporary-file-xyz-permission-denied-when-using-virtiofs/125473/3)
RUN curl -o /tmp/libc6_2.36-8_$(dpkg --print-architecture).deb http://ftp.fr.debian.org/debian/pool/main/g/glibc/libc6_2.36-8_$(dpkg --print-architecture).deb; \
	dpkg -i /tmp/libc6_2.36-8_$(dpkg --print-architecture).deb; \
	rm /tmp/libc6_2.36-8_$(dpkg --print-architecture).deb

RUN curl -o /tmp/sed_4.9-1_$(dpkg --print-architecture).deb http://ftp.fr.debian.org/debian/pool/main/s/sed/sed_4.9-1_$(dpkg --print-architecture).deb; \
	dpkg -i /tmp/sed_4.9-1_$(dpkg --print-architecture).deb; \
	rm /tmp/sed_4.9-1_$(dpkg --print-architecture).deb

# Install Blackfire
RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION.(PHP_ZTS ? '-zts' : '');") \
&& architecture=$(uname -m) \
&& curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/$architecture/$version \
&& mkdir -p /tmp/blackfire \
&& tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
&& mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get ('extension_dir');")/blackfire.so \
&& printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8307\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
&& rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz

RUN a2enmod ssl rewrite

# Install xdebug
ARG test_env=0
RUN if [ "$test_env" = "1" ];then \
	pecl install xdebug; \
	docker-php-ext-enable xdebug; \
	{ \
		echo "xdebug.start_with_request=yes";  \
		echo "xdebug.client_host=127.0.0.1";  \
		echo "xdebug.mode=debug,profile,trace";  \
		echo "xdebug.mode=coverage";  \
		echo "xdebug.idekey=docker";  \
   		 echo 'memory_limit = 1024M'; \
	} >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
	else \
		echo "[BUILD INFO] : Xdebug is not required"; \
	fi

# Set PHP settings
RUN { \
echo 'file_uploads = On'; \
echo 'upload_max_filesize = 64M'; \
echo 'post_max_size = 64M'; \
echo 'memory_limit = 1024M'; \
echo 'max_execution_time = 600'; \
echo 'max_input_time = 600'; \
#echo 'display_errors = On'; \
#echo 'display_startup_errors = On'; \
#echo 'error_reporting = E_ALL'; \
} >> /usr/local/etc/php/php.ini

# Copy SSL certs and Tchooz installation files
COPY apache/certs/localhost.pem /usr/local/apache2/conf/ssl/localhost.pem
COPY apache/certs/localhost-key.pem /usr/local/apache2/conf/ssl/localhost-key.pem
COPY ["./scripts/entrypoint.sh","/entrypoint.sh"]
COPY ["./installation/templates","/templates"]
COPY ["./installation/language","/language"]
COPY ["./installation/logo.png","/core/logo.png"]

ENTRYPOINT [ "/entrypoint.sh"]
CMD ["apache2-foreground"]

