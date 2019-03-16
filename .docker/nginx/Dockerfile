FROM debian:jessie

MAINTAINER Matthieu POIGNANT <matthieu.poignant@gmail.com>

RUN apt-get update && apt-get install -y \
    nginx

ADD conf.d/nginx.conf /etc/nginx/
ADD conf.d/symfony.conf /etc/nginx/sites-available/

RUN ln -s /etc/nginx/sites-available/symfony.conf /etc/nginx/sites-enabled/symfony
RUN rm /etc/nginx/sites-enabled/default

RUN echo "upstream php-upstream { server php:9000; }" > /etc/nginx/conf.d/upstream.conf

RUN usermod -u 1000 www-data

CMD ["nginx"]
