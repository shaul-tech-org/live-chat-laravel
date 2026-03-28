# Copy Vite build from the app image
ARG APP_IMAGE=registry.shaul.kr/livechat/app:latest
FROM ${APP_IMAGE} AS app-build

# Nginx
FROM nginx:alpine

RUN rm /etc/nginx/conf.d/default.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=app-build /var/www/html/public/build /var/www/html/public/build

EXPOSE 80
