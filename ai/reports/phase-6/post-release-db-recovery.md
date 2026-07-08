# Post-Release Report — Dev DB Wipe & Binlog Recovery + Navy Theme
**Date:** 2026-07-08 | **Branch:** `feature/phase-6-a1-debt-clearing` | **Status:** RESOLVED ✅

## Task: Restore owner data from MySQL binary log + apply Navy UI scheme

### What happened
- 2026-07-07 20:10:57 — dev DB `bintan_prestige` wiped by `migrate:fresh`:
  `php artisan test` hit MySQL because Laravel config was **cached** with the
  MySQL connection. Code, migrations, and tests were always safe in git.
- Schema was re-created with `migrate:fresh --seed` (30 demo products, menus,
  templates, FAQ, home sections) but owner-created data was gone.

### Recovery method (non-destructive, step by step)
1. Created a **separate** DB `bintan_prestige_recovery`; migrated the full
   schema into it via `DB_DATABASE` env override (main DB untouched).
2. Dumped binlogs `000006–000013` (copies in `storage/app/binlog-recovery/`)
   with `mysqlbinlog --skip-gtids --stop-datetime="2026-07-07 20:10:57"
   --rewrite-db="bintan_prestige->bintan_prestige_recovery"`.
   - `--skip-gtids` is **mandatory** when replaying on the same server,
     otherwise already-executed GTIDs are silently skipped.
   - Safety-scanned the dump first: no `DROP DATABASE`, no statements
     qualified with the main DB name.
3. Replayed with `mysql --force`, FK checks off, 20 stub users (originals
   predate the binlog range and their FKs blocked child rows).
4. Three tables still failed row-event replay due to **historical schema drift**
   (columns added mid-history via `AFTER x`): `pages`, `page_blocks`,
   `admin_dashboard_appearances`. Recovered via **era-aware reconstruction**:
   decoded row events to pseudo-SQL (`mysqlbinlog -vv --base64-output=DECODE-ROWS`),
   mapped column ordinals → names per migration era, replayed
   INSERT/UPDATE/DELETE in order, emitted `REPLACE INTO` with proper columns
   (TIMESTAMP epoch → `FROM_UNIXTIME`, ENUM ints → labels). 0 errors, JSON valid.
5. Selective import into the main DB (inside a transaction, after a full
   `mysqldump` backup): empty targets got plain inserts; seeded `menus`/
   `menu_items`/`admin_dashboard_appearances` replaced by the owner's versions.
6. Rebuilt derived caches (`content_entry_index`, relations) by re-saving
   entries through the observer; `php artisan optimize:clear`.

### Recovered into main DB
| Table | Rows | Notes |
|---|---|---|
| site_settings | 79 | brand colors, navigation, footer, identity, contact, social, CTA |
| pages | 5 | lagoi, tanjungpinang, bintan, destinations, blog (draft) |
| page_blocks | 36 | page bodies + 12 ContentEntry body blocks |
| content_types | 2 | blog, article (+2 field groups, 7 fields) |
| content_entries | 4 | all published, JSON data/seo valid |
| media | 4 | DB rows only — files were never lost on disk |
| menus / menu_items | 3 / 16 | owner's header + footer menus |
| admin_dashboard_appearances | 1 | owner's light preset |
| builder_patterns | 1 | "Main Content" |

All recovered FKs (`author_id`/`uploaded_by` = 2) matched the existing
`admin@giattech.com` user — no FK normalization needed.

**Unrecoverable** (base rows predate binlog range 2026-06-04): edits to
`page_sections`, `faqs`, `products`; original user rows. Seeded versions remain.

### Navy theme (owner decision — final UI scheme)
DB-only change, no code touched:
- **Frontend** `site_settings` brand_colors: primary `#0B2545`, gold `#C8A24A`
  (kept as accent), accent/link `#35577D`, body `#F7F9FC`, border `#D8E1EC`,
  title `#12233B`; primary buttons navy → gold hover; CTA gold.
- **Admin** `admin_dashboard_appearances`: preset `navy-light` — sidebar
  `#0B1F3B` (dark navy), primary `#1E3A8A`, accent gold; leftover maroon
  focus-ring cleaned.

### Verification
- Test suite **846/846 pass** (4,203 assertions) — sqlite, guarded.
- Public smoke: `/`, `/pages/{lagoi,tanjungpinang,bintan,destinations}`,
  `/articles` + 3 singles, `/blog`, `/products`, `/sitemap.xml` → 200;
  draft page → 404; `/admin/dashboard` → auth redirect.
- Navy rendering verified in DOM (`--frontend-black: #0B2545`; old `#090806`
  absent).

### Impact
- DB: data import + color values only — **no schema change, no code change**
- Routes / Frontend / Security: none

### Rollback
- Pre-import dump: `storage/app/db-backups/pre-import-20260708.sql`
- Full recovered copy: DB `bintan_prestige_recovery` (drop after confirming)
- Binlog copies: `storage/app/binlog-recovery/` — **do not delete**

### Lessons / guards
1. Never `config:cache` in dev — this caused the wipe. Use `optimize:clear`.
2. `tests/TestCase.php` guard refuses non-sqlite test DB — keep it.
3. `--skip-gtids` required for same-server binlog replay.
4. Periodic `mysqldump` of the dev DB is cheap insurance (Phase 8 candidate,
   partially pulled forward).

### Next
Phase 6.1 (interim dashboard & media UX) → then Phase 7 (i18n).
