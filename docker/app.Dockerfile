FROM ubuntu:xenial

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

WORKDIR /home/ipshark

RUN cd /home/ipshark
RUN npm install -g yarn

# need to install node, npm, & composer depending on the system that you're on

CMD /home/ipshark/docker/app.install.sh
