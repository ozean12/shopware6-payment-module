#!/usr/bin/env bash
set -e

BASEDIR=$(cd `dirname $0` && pwd)
PLUGIN_DIR=$(dirname "$BASEDIR")
PLUGIN_NAME="BilliePaymentSW6"
BUILD_DIR=$(dirname "PLUGIN_DIR")/build/

rm -rf "$BUILD_DIR"
mkdir -p build/dist/"$PLUGIN_NAME"
tar --exclude-from="$BASEDIR"/.release_exclude -czf "$BUILD_DIR"/dist.tar.gz .
tar -xzf "$BUILD_DIR"/dist.tar.gz -C "$BUILD_DIR"/dist/"$PLUGIN_NAME"


composer remove shopware/core --no-install --ignore-platform-reqs -d "$BUILD_DIR"/dist/"$PLUGIN_NAME"
composer install --ignore-platform-reqs --no-dev -d "$BUILD_DIR"/dist/"$PLUGIN_NAME"

rm "$BUILD_DIR"/dist/"$PLUGIN_NAME"/composer.json
rm "$BUILD_DIR"/dist/"$PLUGIN_NAME"/composer.lock
cp "$PLUGIN_DIR"/composer.json "$BUILD_DIR"/dist/"$PLUGIN_NAME"/composer.json
composer remove phpstan/phpstan friendsofphp/php-cs-fixer --dev --no-install --no-update --ignore-platform-reqs -d "$BUILD_DIR"/dist/"$PLUGIN_NAME"

rm -rf "$BUILD_DIR"/dist/"$PLUGIN_NAME"/vendor/billie/api-php-sdk/tests
rm -rf "$BUILD_DIR"/dist/"$PLUGIN_NAME"/vendor/billie/api-php-sdk/.git
rm -rf "$BUILD_DIR"/dist.tar.gz


(cd "$BUILD_DIR"/dist && zip -r "$PLUGIN_NAME"-github.zip "$PLUGIN_NAME")

# somebody has setup the module in the shopware store as proprietary. So we need to change the license to pass the checks
sed -i 's#"MIT"#"proprietary"#g' "$BUILD_DIR"/dist/"$PLUGIN_NAME"/composer.json
(cd "$BUILD_DIR"/dist && zip -r "$PLUGIN_NAME"-shopware.zip "$PLUGIN_NAME")
