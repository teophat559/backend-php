# Deployment to VPS

This repo includes a GitHub Actions workflow to deploy to your VPS over SSH using rsync.

## 1) Prerequisites
- A deploy user on the VPS with SSH access and write permission to the target path.
- PHP and a web server on VPS (Apache). For first-time provisioning you can run `deploy/server_setup.sh` via the workflow.

## 2) Required repository secrets
Add these in GitHub → Settings → Secrets and variables → Actions:

- SSH_PRIVATE_KEY: Private key for the deploy user (ed25519 recommended).
- VPS_HOST: VPS IP or hostname.
- VPS_PORT: SSH port (e.g., `22`).
- VPS_USER: SSH username (e.g., `deploy`).
- VPS_PATH: Absolute path on VPS (e.g., `/var/www/backend-php`).
- DOMAIN: Your domain (e.g., `specialprogram2025.online`).
- DB_NAME: Database name (e.g., `specialprogram2025`).
- DB_USER: Database user.
- DB_PASS: Database password.

## 3) First-time server setup (optional)
Use the workflow “Run workflow” (workflow_dispatch) and set `run_setup=true`. This uploads and executes `deploy/server_setup.sh` on the VPS to:
- Install and configure Apache/PHP/MySQL (cross-distro best-effort).
- Create a virtual host for your DOMAIN, creating `VPS_PATH` if needed.
- Create `.env` in `VPS_PATH` with DB and app settings.
- Import `setup-database.sql` if present.

## 4) Auto deploy on push to main
Push to `main` → workflow syncs code to `VPS_PATH` (see `.deployignore` for excludes), installs composer deps (if composer exists), ensures permissions, and reloads Apache.

## 5) Notes
- Update `.deployignore` to control what is uploaded.
- If you prefer to ship `vendor/`, remove it from `.deployignore`.
- For multiple environments, duplicate the workflow with different secrets or use environments.

## 6) Troubleshooting
- Check Actions logs for SSH/rsync errors.
- On the VPS, check Apache error logs and permissions.
- Ensure the deploy user owns `VPS_PATH` and the web server user can read it.
