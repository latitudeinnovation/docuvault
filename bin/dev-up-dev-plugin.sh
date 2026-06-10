#!/bin/bash
set -euo pipefail

compose_file="docker-compose-dev-plugin.yaml"
composer_file="composer.dev-plugin.json"
composer_lock="composer.dev-plugin.lock"

for package_path in "../laravel-raraxuan" "../filament-raraxuan"; do
    if [ ! -d "${package_path}" ]; then
        echo "Missing ${package_path}. Clone it next to docuvault before starting plugin development."
        exit 1
    fi
done

docker compose -f "${compose_file}" up -d

if [ -f "${composer_lock}" ]; then
    docker compose -f "${compose_file}" exec app env COMPOSER="${composer_file}" composer update latitudeinnovation/filament-raraxuan latitudeinnovation/laravel-raraxuan -W --no-interaction
else
    docker compose -f "${compose_file}" exec app env COMPOSER="${composer_file}" composer update --no-interaction
fi