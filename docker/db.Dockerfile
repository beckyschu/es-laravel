FROM mysql:5.7
ENV MYSQL_CONTAINER_NAME ipshark-mysql
ENV MYSQL_DATABASE ipshark
ENV MYSQL_USER ipshark
ENV MYSQL_PASSWORD ipshark
ENV MYSQL_ALLOW_EMPTY_PASSWORD yes

ADD ./database/initial.sql /docker-entrypoint-initdb.d/initial.sql
