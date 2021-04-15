#!/usr/bin/env bash

BASEDIR=$(cd `dirname $0` && pwd)
PLUGIN_DIR=$(dirname "$BASEDIR")

echo "TEST PHP 7.2"
/usr/bin/php7.2 "$PLUGIN_DIR"/vendor/bin/phpstan analyse -c "$PLUGIN_DIR"/phpstan.neon;

echo "TEST PHP 7.3"
/usr/bin/php7.3 "$PLUGIN_DIR"/vendor/bin/phpstan analyse -c "$PLUGIN_DIR"/phpstan.neon;

echo "TEST PHP 7.4"
/usr/bin/php7.4 "$PLUGIN_DIR"/vendor/bin/phpstan analyse -c "$PLUGIN_DIR"/phpstan.neon;

