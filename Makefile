SHELL := /usr/bin/env bash

RUN_DIR := .run
LOG_DIR := $(RUN_DIR)/logs
APP_PORT := 8000
VITE_PORT := 5173

define confirm
	@read -p "$(1) [y/N] " -n 1 -r ans; echo; \
	if [[ ! $$ans =~ ^[Yy]$$ ]]; then echo "Dibatalkan."; exit 1; fi
endef

.PHONY: help setup build dev restart start stop status logs migrate seed db-fresh db-reset test lint format

help: ## Tampilkan daftar perintah
	@awk 'BEGIN {FS = ":.*## "; print ""} /^[a-zA-Z0-9_-]+:.*## / {printf "  make %-12s %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo

setup: ## Install dependency, salin .env, buat key, dan link storage
	composer install
	test -f .env || cp .env.example .env
	grep -q '^APP_KEY=$$' .env && php artisan key:generate --ansi || true
	php artisan storage:link
	npm install
	@echo "Selesai. Isi kredensial DB di .env lalu jalankan: make migrate"

build: ## Install dependency dan build asset frontend
	composer install
	npm install
	npm run build

dev: build restart ## build lalu restart semua service

restart: stop start ## Hentikan lalu jalankan semua service

start: ## Jalankan aplikasi dan vite di latar belakang, PID di .run/
	@mkdir -p $(RUN_DIR) $(LOG_DIR)
	@setsid nohup sh -c 'echo $$$$ > $(RUN_DIR)/app.pid; exec php artisan serve --port=$(APP_PORT)' >> $(LOG_DIR)/app.log 2>&1 &
	@setsid nohup sh -c 'echo $$$$ > $(RUN_DIR)/vite.pid; exec npm run dev -- --port $(VITE_PORT)' >> $(LOG_DIR)/vite.log 2>&1 &
	@echo "Semua service jalan. Log di $(LOG_DIR)."

stop: ## Hentikan semua service
	@-for svc in app vite; do \
		if [ -f $(RUN_DIR)/$$svc.pid ]; then \
			kill -TERM -$$(cat $(RUN_DIR)/$$svc.pid) 2>/dev/null || true; \
			rm -f $(RUN_DIR)/$$svc.pid; \
		fi \
	done
	@echo "Semua service dihentikan."

status: ## Cek service yang jalan
	@-for svc in app vite; do \
		if [ -f $(RUN_DIR)/$$svc.pid ] && kill -0 $$(cat $(RUN_DIR)/$$svc.pid) 2>/dev/null; then \
			echo "$$svc: jalan (pid $$(cat $(RUN_DIR)/$$svc.pid))"; \
		else \
			echo "$$svc: mati"; \
		fi \
	done

logs: ## Lihat log tiap service
	@-for svc in app vite; do \
		echo "== $$svc =="; \
		tail -n 30 $(LOG_DIR)/$$svc.log 2>/dev/null || echo "belum ada log"; \
	done

migrate: ## Jalankan migration
	php artisan migrate

seed: ## Isi data awal
	php artisan db:seed

db-fresh: ## Drop semua tabel, migrate ulang, seed (butuh konfirmasi)
	$(call confirm,Drop semua tabel lalu migrate ulang dan seed?)
	php artisan migrate:fresh --seed

db-reset: ## Rollback semua migration, migrate ulang (butuh konfirmasi)
	$(call confirm,Rollback semua migration lalu migrate ulang?)
	php artisan migrate:refresh

test: ## Jalankan seluruh test
	php artisan test

lint: ## Cek format kode dengan Pint
	./vendor/bin/pint --test

format: ## Rapikan format kode dengan Pint
	./vendor/bin/pint
