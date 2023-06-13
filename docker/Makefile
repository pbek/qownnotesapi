all: check-code test sign-app

.PHONY: bash check-code sign-app show-log test build

# Try to use "docker compose" and fall back to "docker-compose" if not available
DOCKER_COMPOSE := $(shell docker compose > /dev/null && echo docker compose || echo docker-compose)

build:
	$(DOCKER_COMPOSE) build

fetch-db:
	$(DOCKER_COMPOSE) run --rm app su -c "cp data/mydb.db apps/qownnotesapi" www-data

push-db:
	$(DOCKER_COMPOSE) run --rm app su -c "cp apps/qownnotesapi/mydb.db data" www-data

bash:
	$(DOCKER_COMPOSE) run --rm app su -c "bash" www-data

check-code:
	$(DOCKER_COMPOSE) run --rm app su -c "./occ app:check-code qownnotesapi" www-data

sign-app:
	$(DOCKER_COMPOSE) run --rm app ../sign-app.sh www-data

sign-app-owncloud:
	$(DOCKER_COMPOSE) run --rm app ../sign-app-owncloud.sh

test:
	$(DOCKER_COMPOSE) run --rm app su -c "cd apps/qownnotesapi && make test" www-data

show-log:
	$(DOCKER_COMPOSE) run --rm app tail -f /var/www/html/data/nextcloud.log
