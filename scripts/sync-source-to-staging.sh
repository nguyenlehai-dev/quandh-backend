#!/usr/bin/env bash
set -euo pipefail

PROJECTS_ROOT="/home/vpsroot/projects"

BACKEND_SOURCE="${PROJECTS_ROOT}/backend/quandh-backend"
FRONTEND_SOURCE="${PROJECTS_ROOT}/frontend/quandh-frontend"
TARGET_BRANCH="${TARGET_BRANCH:-staging}"

ensure_branch() {
  local repo="$1"
  local branch="$2"
  local label="$3"

  git -C "${repo}" fetch origin "${branch}"
  git -C "${repo}" rev-parse --verify "origin/${branch}" >/dev/null
  echo "[sync-staging] ${label}: origin/${branch} is available"
}

echo "[sync-staging] Staging flow uses origin/${TARGET_BRANCH} as the test release source"
ensure_branch "${BACKEND_SOURCE}" "${TARGET_BRANCH}" "backend"
ensure_branch "${FRONTEND_SOURCE}" "${TARGET_BRANCH}" "frontend"

echo "[sync-staging] Deploying already-built staging images"
"$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/deploy-image-staging.sh"
