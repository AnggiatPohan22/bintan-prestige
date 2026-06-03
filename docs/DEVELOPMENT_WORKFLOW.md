# Development Workflow

This project uses a branch-based workflow so production code stays stable while new CMS modules are being built and tested.

## Branch Strategy

- `main` is stable and production-ready.
- `develop` is the staging and testing branch.
- `feature/*` is for new features.
- `fix/*` is for bug fixes.
- `refactor/*` is for structure cleanup and maintainability work.

## Standard Feature Flow

```bash
git checkout develop
git pull origin develop
git checkout -b feature/example-name
```

Work on the feature, then review the changes:

```bash
git status
git add .
git commit -m "Clear commit message"
git push -u origin feature/example-name
```

Merge into `develop` after testing:

```bash
git checkout develop
git pull origin develop
git merge feature/example-name
git push origin develop
```

## Rules

- Never code directly on `main`.
- Avoid coding directly on `develop` for large changes.
- One feature equals one branch.
- Test before merge.
- Merge to `main` only when a stable milestone is complete.
- Keep commits focused and messages clear.

## Local Checks

Run the relevant checks before merging:

```bash
php artisan optimize:clear
php artisan route:list
php artisan view:clear
npm run dev
php artisan serve
```
