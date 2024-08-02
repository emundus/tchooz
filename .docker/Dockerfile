FROM joomla:5.0.1-php8.2-apache

RUN apt-get update && apt-get install -y libz-dev libmemcached-dev libzip-dev && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-enable memcached
RUN docker-php-ext-install zip

RUN apt-get update && apt-get install -y libmagickwand-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*
#RUN printf "\n" | pecl install imagick
RUN docker-php-ext-enable imagick

# Upgrade sed on 4.9 for bugfix with VirtioFS on MacOS (cf. https://forums.docker.com/t/sed-couldnt-open-temporary-file-xyz-permission-denied-when-using-virtiofs/125473/3)
RUN curl -o /tmp/libc6_2.36-8_$(dpkg --print-architecture).deb http://ftp.fr.debian.org/debian/pool/main/g/glibc/libc6_2.36-8_$(dpkg --print-architecture).deb; \
	dpkg -i /tmp/libc6_2.36-8_$(dpkg --print-architecture).deb; \
	rm /tmp/libc6_2.36-8_$(dpkg --print-architecture).deb

RUN curl -o /tmp/sed_4.9-1_$(dpkg --print-architecture).deb http://ftp.fr.debian.org/debian/pool/main/s/sed/sed_4.9-1_$(dpkg --print-architecture).deb; \
	dpkg -i /tmp/sed_4.9-1_$(dpkg --print-architecture).deb; \
	rm /tmp/sed_4.9-1_$(dpkg --print-architecture).deb


# nvm environment variables
ENV NVM_VERSION v0.40.0
ENV NVM_DIR /usr/local/nvm
ENV NODE_VERSION v18.20.2

RUN mkdir $NVM_DIR

# install nvm
# https://github.com/creationix/nvm#install-script
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/$NVM_VERSION/install.sh | bash

ENV NODE_PATH $NVM_DIR/$NODE_VERSION/lib/node_modules
ENV PATH $NVM_DIR/versions/node/$NODE_VERSION/bin:$PATH

RUN echo "source $NVM_DIR/nvm.sh && \
    nvm install $NODE_VERSION && \
    nvm alias default $NODE_VERSION && \
    nvm use default" | bash

RUN ln -sf NVM_DIR/versions/node/$NODE_VERSION/bin/node /usr/bin/nodejs
RUN ln -sf NVM_DIR/versions/node/$NODE_VERSION/bin/node /usr/bin/node
RUN ln -sf NVM_DIR/versions/node/$NODE_VERSION/bin/npm /usr/bin/npm

# confirm installation
RUN node -v
RUN npm -v

# Copy and set custom entrypoint script
COPY ["./scripts/entrypoint.sh","/entrypoint.sh"]
COPY ["./installation/templates","/templates"]
COPY ["./installation/language","/language"]
COPY ["./installation/logo.png","/core/logo.png"]
ENTRYPOINT [ "/entrypoint.sh"]

CMD ["apache2-foreground"]

