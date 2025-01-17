# Executables
PHP      = php
COMPOSER = composer

# Misc
.DEFAULT_GOAL = help

.PHONY: help
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
.PHONY: composer
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

.PHONY: composer-install
composer-install: ## Install Composer dependencies according to the current composer.lock file
composer-install: c = install
composer-install: composer

.PHONY: composer-update
composer-update: ## Update Composer dependencies
composer-update: c = update
composer-update: composer

## —— Analysis 🔎 ——————————————————————————————————————————————————————————————
.PHONY: lint
lint: phpstan ecs ## Analyze code and show errors (PHPStan, ECS)

.PHONY: lint-fix
lint-fix: ecs-fix ## Analyze code and fix errors (ECS)

.PHONY: phpstan
phpstan: ## Run PHPStan and show errors
	@$(eval c ?=)
	@$(PHP) vendor/bin/phpstan analyse --memory-limit=-1 $(c)

.PHONY: phpstan-cc
phpstan-cc: ## Clear PHPStan cache
	@rm -rf var/cache/phpstan

.PHONY: ecs
ecs: ## Run Easy Coding Standard (ECS) and show errors
	@$(eval c ?=)
	@$(PHP) vendor/bin/ecs check --memory-limit=-1 $(c)

.PHONY: ecs-fix
ecs-fix: ## Run Easy Coding Standard (ECS) and fix errors
	@$(PHP) vendor/bin/ecs check --fix --memory-limit=-1
