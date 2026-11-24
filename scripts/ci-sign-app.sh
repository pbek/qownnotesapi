#!/bin/sh
# Package, sign and test in a disposable Nextcloud, not the developer's instance.
set -eu
cd "$(CDPATH='' cd -- "$(dirname -- "$0")/.." && pwd)"
: "${APP_PRIVATE_KEY:?APP_PRIVATE_KEY is required}"
: "${APP_PUBLIC_CRT:?APP_PUBLIC_CRT is required}"
private_key=${APP_PRIVATE_KEY}
certificate=${APP_PUBLIC_CRT}
unset APP_PRIVATE_KEY APP_PUBLIC_CRT
VERSION=$(python3 scripts/release_metadata.py)

WORK_DIR=$(mktemp -d)
CONTAINER="qownnotesapi-release-$(basename "${WORK_DIR}" | tr '[:upper:]' '[:lower:]')"
cleanup() {
  docker rm -fv "${CONTAINER}" >/dev/null 2>&1 || true
  rm -rf "${WORK_DIR}"
}
trap cleanup EXIT
trap 'exit 1' HUP INT TERM

docker run -d --name "${CONTAINER}" \
  --tmpfs /run/signing:mode=0700 \
  -e NEXTCLOUD_ADMIN_USER=admin -e NEXTCLOUD_ADMIN_PASSWORD=release-test-only \
  -e SQLITE_DATABASE=nextcloud -e NEXTCLOUD_TRUSTED_DOMAINS=localhost \
  nextcloud:35.0.1-apache@sha256:8f2f0e93aca5e9457019933936dea1f313052e72c696b4aecb31580a208b859f >/dev/null
ready=false
for _ in $(seq 1 90); do
  if docker exec "${CONTAINER}" curl -fsS http://localhost/status.php 2>/dev/null |
    python3 -c 'import json,sys; data=json.load(sys.stdin); sys.exit(not (data["installed"] and not data["maintenance"]))' 2>/dev/null; then
    ready=true
    break
  fi
  sleep 2
done
if [ "${ready}" != true ]; then
  echo "Nextcloud did not initialize within 180 seconds" >&2
  exit 1
fi

APP_DIR=/tmp/release/qownnotesapi
ARCHIVE=qownnotesapi-nc.tar.gz
docker exec "${CONTAINER}" mkdir -p "${APP_DIR}"
tar -cf "${WORK_DIR}/app.tar" -T release-files.txt
docker exec -i "${CONTAINER}" tar -xf - -C "${APP_DIR}" <"${WORK_DIR}/app.tar"
printf '%s\n' "${private_key}" |
  docker exec -i "${CONTAINER}" sh -c 'umask 077; cat > /run/signing/app.key'
unset private_key
printf '%s\n' "${certificate}" >"${WORK_DIR}/app.crt"
docker exec -i "${CONTAINER}" sh -c 'cat > /run/signing/app.crt' <"${WORK_DIR}/app.crt"
docker exec "${CONTAINER}" chown -R www-data:www-data /run/signing "${APP_DIR}"
docker exec -u www-data "${CONTAINER}" php occ integrity:sign-app \
  --privateKey=/run/signing/app.key --certificate=/run/signing/app.crt --path="${APP_DIR}"
docker exec "${CONTAINER}" tar --owner=0 --group=0 -czf "/tmp/${ARCHIVE}" -C /tmp/release qownnotesapi
docker exec "${CONTAINER}" openssl dgst -sha512 -sign /run/signing/app.key \
  -out /run/signing/archive.sig "/tmp/${ARCHIVE}"
docker exec "${CONTAINER}" rm /run/signing/app.key
docker exec "${CONTAINER}" openssl base64 -A -in /run/signing/archive.sig >"${WORK_DIR}/${ARCHIVE}.sig"

docker cp "${CONTAINER}:/tmp/${ARCHIVE}" "${WORK_DIR}/"
if [ "$(stat -c '%s' "${WORK_DIR}/${ARCHIVE}")" -gt 20971520 ]; then
  echo "Signed archive exceeds the App Store's 20 MiB limit" >&2
  exit 1
fi
openssl x509 -in "${WORK_DIR}/app.crt" -pubkey -noout >"${WORK_DIR}/public.pem"
openssl base64 -d -A -in "${WORK_DIR}/${ARCHIVE}.sig" -out "${WORK_DIR}/signature"
openssl dgst -sha512 -verify "${WORK_DIR}/public.pem" -signature "${WORK_DIR}/signature" "${WORK_DIR}/${ARCHIVE}"

# Verify and install the extracted release, not the unsigned source tree.
docker exec "${CONTAINER}" tar -xzf "/tmp/${ARCHIVE}" -C /var/www/html/custom_apps
docker exec "${CONTAINER}" test -s /var/www/html/custom_apps/qownnotesapi/appinfo/signature.json
docker exec -u www-data "${CONTAINER}" php occ integrity:check-app qownnotesapi \
  --path=/var/www/html/custom_apps/qownnotesapi
docker exec -u www-data "${CONTAINER}" php occ app:enable qownnotesapi
docker exec "${CONTAINER}" curl -fsS -u admin:release-test-only \
  http://localhost/index.php/apps/qownnotesapi/api/v1/note/app_info |
  python3 -c '
import json, sys
data = json.load(sys.stdin)
if data["app_version"] != sys.argv[1]:
    sys.exit(f"Unexpected app version: {data}")
' "${VERSION}"

mkdir -p build
cp "${WORK_DIR}/${ARCHIVE}" "${WORK_DIR}/${ARCHIVE}.sig" build/
(cd build && sha256sum "${ARCHIVE}" >"${ARCHIVE}.sha256")
echo "Signed archive integrity and installed-app smoke test passed: build/${ARCHIVE}"
