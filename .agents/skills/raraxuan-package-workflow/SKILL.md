---
name: raraxuan-package-workflow
description: "Use this skill whenever working with Raraxuan AI package integration, latitudeinnovation/filament-raraxuan, latitudeinnovation/laravel-raraxuan, Packagist package versions, Composer package metadata, or local Raraxuan plugin development in this DocuVault Laravel and Filament application."
license: MIT
metadata:
  author: laravel
---

# Raraxuan Package Workflow

Use this skill for Raraxuan AI integration work, especially anything involving
`latitudeinnovation/filament-raraxuan`, `latitudeinnovation/laravel-raraxuan`,
Packagist package metadata, Composer constraints, or local package/plugin
development.

## First Checks

- Inspect `composer.json`, `composer.lock`, and installed vendor code before assuming package versions, service providers, config keys, commands, facades, or public APIs.
- If `composer.lock` is absent or does not match the package being discussed, use Composer metadata as the next source of truth.
- When package version, release notes, dependency constraints, installation steps, or package availability may have changed, check Packagist or Composer metadata before giving guidance.
- Prefer Laravel Boost `search-docs` for Laravel, Filament, and Livewire framework APIs, but use Packagist metadata and package source for Raraxuan-specific behavior.

## App Integration

- For normal application usage, work from the root `composer.json` and installed package versions.
- Do not change package constraints, repositories, Composer plugins, or dependency versions without explicit user approval.
- Follow existing Laravel and Filament conventions in the app before introducing new integration patterns.
- Treat AI output from Raraxuan as untrusted until the app validates, stores, and reviews it through the existing document workflow.

## Local Package Development

- Use the local development setup only when the task is about developing or testing the Raraxuan packages themselves.
- Local package development uses `composer.dev-plugin.json`, `composer.dev-plugin.lock`, `docker-compose-dev-plugin.yaml`, and `bin/dev-up-dev-plugin.sh`.
- The local package paths are expected next to this repo at `../laravel-raraxuan` and `../filament-raraxuan`.
- When local package code is mounted through the dev-plugin setup, inspect the mounted package source instead of guessing package internals from Packagist.

## Verification

- Runtime Laravel changes still need focused PHPUnit coverage.
- Agent-only skill edits can be verified by checking that `SKILL.md` exists, has valid YAML frontmatter, and includes the package names in its description so the skill can trigger.
