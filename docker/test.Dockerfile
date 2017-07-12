FROM shincoder/homestead:php7.0

ADD . /home/ipshark
WORKDIR /home/ipshark

ADD ./docker/main.supervisor.conf /etc/supervisor/conf.d/supervisor.conf

RUN npm install -g yarn
RUN /home/ipshark/docker/main.nginx.sh ipshark.app /home/ipshark/public
RUN /home/ipshark/docker/main.install.sh
