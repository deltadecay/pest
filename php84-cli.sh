#!/bin/sh

docker run -it --rm --name php84-cli -v "$PWD":/usr/src/myapp -w /usr/src/myapp php:8.4.3-cli-alpine3.20 php $@

