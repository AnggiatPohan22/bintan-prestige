# STEP GIT-CHECK-01 - Verify Uncommitted Config Files

Date: 2026-06-13
Status: Completed.

## Files Checked

- `.env`
- `.env.example`
- `bootstrap/app.php`

## Git Status Summary

`git status --short` currently shows:

- Modified: `.env.example`
- Modified: `bootstrap/app.php`
- Untracked: `ai/reports/backend/`

The untracked backend report directory is from STEP IMPROVE-03 and is outside the focused config-file check.

## .env Status

- `.env` exists locally.
- `.env` is not tracked by Git.
- `.env` is ignored by `.gitignore`.
- `.env` should remain local only.

Recommended action for `.env`:

- Keep local only.
- Do not add.
- Do not commit.
- Do not print or copy values into docs/reports.

## .env.example Status

Status:

- Modified.
- Tracked by Git.

Diff summary:

- `APP_DEBUG` changed from `true` to `false`.

Security relevance:

- High.
- This supports safer default environment posture and aligns with the security baseline.

Secret risk:

- No real secret value was found in the checked diff.
- `APP_KEY` remains empty.
- Placeholder-style fields such as mail password and cloud secret key remain empty/null.

Recommended action for `.env.example`:

- Commit.
- Do not restore, because `APP_DEBUG=false` is an intentional security-safe default.

## bootstrap/app.php Status

Status:

- Modified.
- Tracked by Git.

Diff summary:

- Adds `ProvisionFirstAdmin` command registration.
- Adds `AdminMiddleware` import.
- Registers the `admin` middleware alias.

Security relevance:

- Critical for the current security/admin access foundation.
- The `admin` middleware alias is required for protected admin routes.
- The `ProvisionFirstAdmin` command registration is required for CLI-only first admin provisioning.

Secret risk:

- No secret value is present in this diff.
- No credential, token, key, password, or API secret was added.

Recommended action for `bootstrap/app.php`:

- Commit.
- Do not restore, because restoring this file would remove admin middleware registration and first-admin command registration.

## Security Relevance

These two tracked changes are security-relevant and should not be treated as disposable local config:

- `.env.example`: safe default for `APP_DEBUG`.
- `bootstrap/app.php`: admin middleware alias and first-admin command registration.

Restoring `bootstrap/app.php` would likely break admin access protection and/or CLI first-admin provisioning.

## Secret Risk

Current secret risk based on inspected files:

- `.env`: local, ignored, not tracked. Do not expose.
- `.env.example`: no real secret found in inspected diff.
- `bootstrap/app.php`: no secret found.

No secret values are included in this report.

## Recommended Action

Recommended:

- Keep `.env` local only.
- Commit `.env.example`.
- Commit `bootstrap/app.php`.
- Commit this git check report if you want the decision recorded.
- Also decide separately whether to commit the untracked STEP IMPROVE-03 backend audit report directory.

Not recommended:

- Do not run `git restore -- bootstrap/app.php` unless you intentionally want to undo security/admin access registration.
- Do not add `.env`.
- Do not commit any real environment value.

## Exact Git Commands To Run

Recommended verification:

```bash
git status --short -- .env .env.example bootstrap/app.php
git diff -- .env.example
git diff -- bootstrap/app.php
```

Recommended staging for these config/security-registration changes and this report:

```bash
git add .env.example bootstrap/app.php ai/reports/git/git-check-01-uncommitted-config-files.md
```

Optional staging for the previous backend audit report:

```bash
git add ai/reports/backend/improve-03-backend-structure-cleanup-audit.md
```

Recommended commit after review:

```bash
git commit -m "chore: record backend audit and security config status"
```

Do not run:

```bash
git add .env
```

Restore command only if you explicitly decide to discard these security-relevant changes:

```bash
git restore -- .env.example bootstrap/app.php
```

## Final Recommendation

Commit `.env.example` and `bootstrap/app.php` with the related security/documentation work. Keep `.env` ignored and local only.
