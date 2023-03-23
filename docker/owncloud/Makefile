.PHONY: bash check-code sign-app show-log test

bash:
	docker-compose exec owncloud su -c "bash" www-data

root-bash:
	docker-compose exec owncloud bash

check-code:
	docker-compose exec owncloud su -c "./occ app:check-code qownnotesapi" www-data

test:
	docker-compose exec owncloud su -c "cd apps/qownnotesapi && make test" www-data
