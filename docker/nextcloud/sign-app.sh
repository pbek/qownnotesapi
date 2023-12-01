#!/bin/sh
########################################################################
# Creates the signature.json for the Nextcloud application
########################################################################

APP_NAME=qownnotesapi
APP_SOURCE=/var/www/html/custom_apps/${APP_NAME}
APP_DEST=/var/www/deploy/${APP_NAME}
CERT_PATH=/var/www/.nextcloud/certificates
DEPLOYMENT_FILE=${APP_SOURCE}/${APP_NAME}-nc.tar.gz

rm -rf ${APP_DEST} && \
mkdir ${APP_DEST} && \
rsync -a --exclude .git* --exclude .gitlab-ci* --exclude .github --exclude screenshot* \
         --exclude docs --exclude tests --exclude vendor --exclude package.* --exclude Makefile \
         --exclude *.db* --exclude docker --exclude *.phar --exclude *.gz --exclude .idea \
         --exclude=shell.nix --exclude=.envrc --exclude .direnv --exclude .codeclimate.yml \
          --exclude .editorconfig --exclude .php_cs* --exclude .php-cs* --exclude .travis.yml \
         ${APP_SOURCE}/ ${APP_DEST} && \
su -m -c "./occ integrity:sign-app \
  --privateKey=${CERT_PATH}/${APP_NAME}.key \
  --certificate=${CERT_PATH}/${APP_NAME}.crt --path=${APP_DEST}" www-data && \
cp ${APP_DEST}/appinfo/signature.json ${APP_SOURCE}/appinfo && \
tar cz ${APP_DEST}/.. > ${DEPLOYMENT_FILE} && \
echo "\nSignature for your app archive:\n" && \
openssl dgst -sha512 -sign ${CERT_PATH}/${APP_NAME}.key ${DEPLOYMENT_FILE} | openssl base64 && \
echo
