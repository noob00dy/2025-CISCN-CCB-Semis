FROM php:8.2.6-apache

ENV PYTHONUNBUFFERED=1 \
	PYTHONDONTWRITEBYTECODE=1

RUN docker-php-ext-configure opcache --enable-opcache \
	&& docker-php-ext-install opcache \
	&& sed -i 's|http://deb.debian.org|https://deb.debian.org|g' /etc/apt/sources.list \
	&& sed -i 's|http://security.debian.org|https://security.debian.org|g' /etc/apt/sources.list \
	&& apt-get update -o Acquire::Retries=5 \
	&& apt-get install -y --no-install-recommends supervisor python3 python3-venv python3-distutils python3-pip \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /app

RUN pip3 install --no-cache-dir flask werkzeug

RUN mkdir -p /app/uploads /app/plugins /app/static/uploads/avatars

COPY . /app

COPY html/ /var/www/html/

COPY php.ini-development /usr/local/etc/php/php.ini

RUN rm -rf /app/html /app/Dockerfile /app/docker-compose.yaml /app/docker-compose.yml /app/php.ini*


COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80 5000

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
