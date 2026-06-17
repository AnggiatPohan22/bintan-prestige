# Security Hardening Guideline

## Main Goal
Protect the Laravel CMS from exposed secrets, unsafe admin access, insecure uploads, XSS, CSRF, SQL injection, and backdoor-like behavior.

## Required References
- Master rule: `AGENTS.md`
- Related skill: `security-skill.md`
- Related guidelines: `01-laravel-mvc-architecture.md`, `02-laravel-boost-workflow.md`, `10-documentation-system.md`

## Required Checks
- `.env`, `APP_KEY`, database credentials, API tokens, and other secrets are not exposed.
- `APP_DEBUG` is not enabled for production.
- Admin routes are protected by auth middleware.
- Sensitive actions use authorization through policies/gates or equivalent checks.
- Forms use CSRF protection.
- User input is validated.
- Output is escaped by default.
- Raw SQL does not concatenate user input.
- Uploads validate type, extension, size, filename, and storage path.
- Suspicious functions are reviewed: `eval`, `shell_exec`, `exec`, `system`, `passthru`, unsafe `base64_decode`.

## Rules
- Never hardcode credentials.
- Never add hidden admin bypasses or superadmin backdoors.
- Never expose private/admin routes to search engines.
- Report suspected leaked secrets immediately and recommend rotation.
- Document every security-sensitive change.
