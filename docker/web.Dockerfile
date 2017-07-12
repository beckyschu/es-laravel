FROM nginx:1.10
WORKDIR /home/ipshark

ADD docker/vhost.conf /etc/nginx/conf.d/default.conf
