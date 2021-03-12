all: check-code test sign-app

.PHONY: bash check-code sign-app show-log test

fetch-db:
	docker-compose run --rm app su -c "cp data/mydb.db apps/qownnotesapi" www-data

push-db:
	docker-compose run --rm app su -c "cp apps/qownnotesapi/mydb.db data" www-data

bash:
	docker-compose run --rm app su -c "bash" www-data

check-code:
	docker-compose run --rm app su -c "./occ app:check-code qownnotesapi" www-data

sign-app:
	docker-compose run --rm app ../sign-app.sh

sign-app-owncloud:
	docker-compose run --rm app ../sign-app-owncloud.sh

test:
	docker-compose run --rm app su -c "cd apps/qownnotesapi && make test" www-data

show-log:
	docker-compose run --rm app tail -f /var/www/html/data/nextcloud.log
