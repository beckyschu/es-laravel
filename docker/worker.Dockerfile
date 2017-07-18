FROM ubuntu:xenial

########## COPIED from app.Dockerfile ############
# Let the container know that there is no tty
ENV DEBIAN_FRONTEND noninteractive
ENV COMPOSER_NO_INTERACTION 1

# Install tools
RUN apt-get update \
	&& apt-get -y install zip unzip \
		git build-essential curl \
		software-properties-common

# Install PHP
# Install php-mbstring = laravel/framework v5.4.27 requires ext-mbstring *
# Install php7.0-xml = phpunit/php-code-coverage 4.0.8 requires ext-dom *
# Install php-curl =  - dropbox/dropbox-sdk v1.1.7 requires ext-curl * -> the requested PHP extension curl is missing from your system.
RUN apt-get -y update \
  && apt-get install -y php7.0 php7.0-mysql php-mbstring php7.0-xml php7.0-sqlite php7.0-curl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node & NPM
RUN curl -sL https://deb.nodesource.com/setup_6.x \
			-o nodesource_setup.sh \
	&& bash nodesource_setup.sh \
	&& apt-get -y install nodejs

########## END COPY from app.Dockerfile ############

# Install tools
RUN apt-get update && apt-get -y install php7.0 supervisor


# Add new user to ubuntu system
RUN groupadd ipshark
RUN useradd -g ipshark -ms /bin/bash ipshark

WORKDIR /home/ipshark

ADD ./docker/worker.supervisor.conf /etc/supervisor/conf.d/supervisor.conf

CMD npm install && \
    /home/ipshark/docker/worker.install.sh && \
    chown -R ipshark:ipshark /home/ipshark/storage /home/ipshark/bootstrap/cache && \
    supervisord
