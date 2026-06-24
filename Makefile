.DEFAULT_GOAL := help

help: ## Muestra esta ayuda
	@echo 'uso: make [target]'
	@echo
	@egrep '^(.+)\:\ ##\ (.+)' ${MAKEFILE_LIST} | column -t -c 2 -s ':#'

start: ## Levanta los contenedores (API + frontend + MySQL)
	docker compose up -d --remove-orphans
	@echo "Frontend: http://localhost:8099"
	@echo "API:      http://localhost:8100/api/health"

stop: ## Detiene los contenedores
	docker compose stop

down: ## Baja contenedores y red (conserva los datos de MySQL)
	docker compose down --remove-orphans

build: ## Construye las imagenes
	docker compose build

rebuild: ## Reconstruye desde cero (borra vendor/, node_modules y los datos de MySQL)
	docker compose down --remove-orphans --volumes
	rm -rf backend/vendor frontend/node_modules
	docker compose build --no-cache && docker compose up -d

reset-data: ## Reinicia SOLO los datos de desarrollo (borra el volumen de MySQL)
	docker compose down --remove-orphans --volumes
	docker compose up -d

logs: ## Logs en vivo (todos los servicios)
	docker compose logs -f

sh: ## Shell dentro del contenedor del API
	docker compose exec api bash

console: ## bin/console (ej: make console c="cache:clear")
	docker compose exec -u www-data api php bin/console $(c)

migrate: ## Aplica las migraciones de Doctrine
	docker compose exec -u www-data api php bin/console doctrine:migrations:migrate --no-interaction

fixtures: ## Carga los datos demo (idempotente)
	docker compose exec -u www-data api php bin/console doctrine:fixtures:load --append --no-interaction

test: ## Corre la suite de tests del backend (PHPUnit sobre MySQL de tests)
	docker compose exec api php bin/phpunit
