FROM shincoder/homestead:php7.0

WORKDIR /home/ipshark

RUN npm install -g yarn
ADD ./docker/main.supervisor.conf /etc/supervisor/conf.d/supervisor.conf

CMD /home/ipshark/docker/main.nginx.sh ipshark.app /home/ipshark/public && \
    /home/ipshark/docker/main.install.sh && \
    chown -R homestead:homestead /home/ipshark/storage /home/ipshark/bootstrap/cache && \
    supervisord
