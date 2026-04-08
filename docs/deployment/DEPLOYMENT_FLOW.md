# QuandH Deployment Flow

## Source Model

- Backend source: `/home/vpsroot/projects/backend/quandh-backend`
- Frontend source: `/home/vpsroot/projects/frontend/quandh-frontend`
- Staging and production run Docker images built from branches, not copied local source.

## Domain Mapping

- `staging` branch -> `https://test.yukimart.io.vn/`
- `prod` branch -> `https://yukimart.io.vn/`

## Release Flow

1. Develop and merge changes into `staging`
2. GitHub Actions builds/pushes the `staging` images
3. Deploy `staging` to `https://test.yukimart.io.vn/`
4. Validate on test
5. Promote the validated commit from `staging` to `prod`
6. GitHub Actions builds/pushes the `prod` images
7. Deploy `prod` to `https://yukimart.io.vn/`

Short form:

- `staging -> test -> promote -> prod`

## SOP

Push/merge the backend release commit into `staging`:

```bash
cd /home/vpsroot/projects/backend/quandh-backend
git status
git add .
git commit -m "fix: mo ta thay doi"
git push origin HEAD:staging
```

Push/merge the frontend release commit into `staging` when frontend changed:

```bash
cd /home/vpsroot/projects/frontend/quandh-frontend
git status
git push origin HEAD:staging
```

After both backend and frontend staging image builds finish, pull/start staging images:

```bash
cd /home/vpsroot/projects/backend/quandh-backend
./scripts/deploy-image-staging.sh
```

Validate staging:

```bash
curl -fsS https://test.yukimart.io.vn/up
```

Promote both backend and frontend from `staging` to `prod`:

```bash
cd /home/vpsroot/projects/backend/quandh-backend
./scripts/promote-staging-to-prod.sh
```

After both production image builds finish, pull/start production images:

```bash
cd /home/vpsroot/projects/backend/quandh-backend
./scripts/deploy-image-prod.sh
```

Validate production:

```bash
curl -fsS https://yukimart.io.vn/up
```

Optional: deploy only the already-built staging image:

```bash
cd /home/vpsroot/projects/backend/quandh-backend
./scripts/deploy-image-staging.sh
```

Optional: deploy only the already-built production image:

```bash
cd /home/vpsroot/projects/backend/quandh-backend
./scripts/deploy-image-prod.sh
```

## Runtime Config

- Backend `staging` image: `ghcr.io/nguyenlehai-dev/quandh-backend:staging`
- Backend `prod` image: `ghcr.io/nguyenlehai-dev/quandh-backend:prod`
- Frontend `staging` image: `ghcr.io/nguyenlehai-dev/quandh-frontend:staging`
- Frontend `prod` image: `ghcr.io/nguyenlehai-dev/quandh-frontend:prod`
- Staging compose: `docker-compose.image.staging.yml`
- Production compose: `docker-compose.image.prod.yml`
- Staging env: `/home/vpsroot/projects/backend/quandh-backend/deploy/staging/.env`
- Production env: `/home/vpsroot/projects/backend/quandh-backend/deploy/prod/.env`

## Ports

- Staging API: `127.0.0.1:8020`
- Staging frontend: `127.0.0.1:3020`
- Production API: `127.0.0.1:8021`
- Production frontend: `127.0.0.1:3030`

External reverse proxy should route:

- `https://test.yukimart.io.vn/` -> `127.0.0.1:3020`
- `https://yukimart.io.vn/` -> `127.0.0.1:3030`

BT/nginx vhost templates:

- `deploy/nginx/test.yukimart.io.vn.btpanel.conf`
- `deploy/nginx/yukimart.io.vn.btpanel.conf`

If `https://test.yukimart.io.vn/` returns `403 Forbidden`, verify the host vhost is not pointing at an empty static root. It must either use the staging template above or otherwise proxy to `127.0.0.1:3020` after `./scripts/deploy-image-staging.sh` has started the staging containers.

## Rules

- Do not copy source between environments manually.
- Promote only through branches and Git history.
- Deploy server pulls image tags that match the branch.
- Set `RUN_MIGRATIONS=true` in the environment only when you intentionally want the API container to run migrations at startup.
