#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="/home/vpsroot/projects/backend/quandh-backend"
COMPOSE_FILE="${ROOT_DIR}/docker-compose.image.prod.yml"
DEPLOY_DIR="${ROOT_DIR}/deploy/prod"
APP_ENV_FILE="${DEPLOY_DIR}/.env"
BACKEND_SHA_FILE="${DEPLOY_DIR}/backend.sha"
FRONTEND_SHA_FILE="${DEPLOY_DIR}/frontend.sha"
FRONTEND_SOURCE="/home/vpsroot/projects/frontend/quandh-frontend"

mkdir -p "${DEPLOY_DIR}"

if [[ ! -f "${APP_ENV_FILE}" ]]; then
  echo "[prod-image] Missing env file: ${APP_ENV_FILE}" >&2
  echo "[prod-image] Create it from .env.example and set APP_KEY, APP_URL, FRONTEND_URL, DB_*." >&2
  exit 1
fi

echo "[prod-image] Pulling production images"
docker compose --env-file "${APP_ENV_FILE}" -f "${COMPOSE_FILE}" -p quandh-prod pull

echo "[prod-image] Starting production containers from images"
docker compose --env-file "${APP_ENV_FILE}" -f "${COMPOSE_FILE}" -p quandh-prod up -d

git -C "${ROOT_DIR}" fetch origin prod >/dev/null 2>&1 || true
git -C "${FRONTEND_SOURCE}" fetch origin prod >/dev/null 2>&1 || true
git -C "${ROOT_DIR}" rev-parse "origin/prod" > "${BACKEND_SHA_FILE}" 2>/dev/null || true
git -C "${FRONTEND_SOURCE}" rev-parse "origin/prod" > "${FRONTEND_SHA_FILE}" 2>/dev/null || true

echo "[prod-image] Waiting for API health"
for _ in $(seq 1 30); do
  if curl -fsS http://127.0.0.1:8021/up >/dev/null 2>&1; then
    echo "[prod-image] API is healthy"
    exit 0
  fi
  sleep 2
done

echo "[prod-image] API did not become healthy in time" >&2
docker compose --env-file "${APP_ENV_FILE}" -f "${COMPOSE_FILE}" -p quandh-prod logs --tail=100 api >&2
exit 1
