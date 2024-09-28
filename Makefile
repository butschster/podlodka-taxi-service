build:
	docker compose up --no-start;

start:
	docker compose --env-file .docker/.env up --remove-orphans -d;

up: build start

stop:
	docker compose stop;

down:
	docker compose down;

restart:
	docker compose restart;

list:
	docker compose ps;

log-tail:
	docker compose logs --tail=50 -f;

# =========================

bash-app:
	docker compose exec -t app /bin/sh;

reset-app:
	docker compose exec app rr reset;

reset: reset-app
