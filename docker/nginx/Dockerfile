FROM nginx:1.25-alpine

RUN apk add --no-cache curl

COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public

EXPOSE 80

HEALTHCHECK --interval=10s --timeout=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1
