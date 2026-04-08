#!/usr/bin/env bash
set -euo pipefail

PROJECTS_ROOT="/home/vpsroot/projects"

BACKEND_SOURCE="${PROJECTS_ROOT}/backend/quandh-backend"
FRONTEND_SOURCE="${PROJECTS_ROOT}/frontend/quandh-frontend"

promote_branch() {
  local repo="$1"
  local source_branch="$2"
  local target_branch="$3"
  local label="$4"

  git -C "${repo}" fetch origin "${source_branch}"
  git -C "${repo}" push origin "refs/remotes/origin/${source_branch}:refs/heads/${target_branch}"
  echo "[promote-prod] ${label}: ${source_branch} -> ${target_branch} pushed"
}

echo "[promote-prod] Promoting backend staging -> prod"
promote_branch "${BACKEND_SOURCE}" "staging" "prod" "backend"

echo "[promote-prod] Promoting frontend staging -> prod"
promote_branch "${FRONTEND_SOURCE}" "staging" "prod" "frontend"

cat <<'MESSAGE'
[promote-prod] Branch promotion completed.
[promote-prod] Wait for GitHub Actions to build and push the prod images, then run:
[promote-prod]   ./scripts/deploy-image-prod.sh
MESSAGE
