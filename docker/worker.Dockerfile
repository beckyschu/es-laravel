FROM shincoder/homestead:php7.0

WORKDIR /home/ipshark

ADD ./docker/worker.supervisor.conf /etc/supervisor/conf.d/supervisor.conf

CMD npm install && \
    /home/ipshark/docker/worker.install.sh && \
    chown -R homestead:homestead /home/ipshark/storage /home/ipshark/bootstrap/cache && \
    supervisord
