FROM node:8.9-alpine

RUN apk add --update --no-cache \
libc6-compat

WORKDIR /usr/src/app
COPY ./stream-server/ .
RUN npm install
EXPOSE 81/TCP
CMD [ "npm", "start" ]

