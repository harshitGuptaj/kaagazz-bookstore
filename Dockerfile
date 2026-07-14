FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

EXPOSE $PORT
CMD ["sh", "-c", "php -S 0.0.0.0:$PORT"]
