# Phase 4 — Plugin & Module System
## Execution Skill File for Claude Code

> **Letakkan file ini di:** `ai/skills/phase4-plugin-module-skill.md`
> **Panggil di awal setiap sesi Phase 4 dengan:** "Read ai/skills/phase4-plugin-module-skill.md first"

---

## 1. Context & Authority

File ini adalah sumber kebenaran tunggal untuk seluruh pekerjaan Phase 4.
Bacaan wajib sebelum menulis satu baris pun kode di Phase 4.

**Authority order untuk Phase 4:**
1. Instruksi eksplisit owner di sesi tersebut
2. `AGENTS.md` (constitution)
3. File ini (`phase4-plugin-module-skill.md`)
4. `ai/guidelines/*`
5. Kode existing yang sudah berjalan

**Branch Git untuk Phase 4:** `feature/phase-4-plugin-system`

---

## 2. Overview Phase 4

**Goal:** Membangun sistem plugin/modul yang memungkinkan fungsionalitas baru
ditambahkan ke CMS tanpa mengubah core codebase. Mirip sistem plugin WordPress
namun berbasis Laravel Service Provider dan event system.

**Estimasi durasi:** 11–12 minggu (Juli–September 2026)

**Komponen utama yang dibangun:**
- Plugin manifest, registry, dan loader
- Hook & filter event system (CmsHooks facade)
- Plugin admin interface (list, activate, deactivate, settings)
- Core Plugin 1: SEO Manager
- Core Plugin 2: Contact Form Builder
- Core Plugin 3: Analytics Dashboard
- Plugin security & sandboxing
- + Improvements dari Phase 1–3 (lihat Seksi 4)

**Success criteria sebelum Phase 4 dianggap selesai:**
- [ ] Plugin dapat diaktifkan/nonaktifkan dari admin tanpa restart server
- [ ] Plugin dapat mendaftarkan admin page dan sidebar menu sendiri
- [ ] Hook system memungkinkan plugin memodifikasi konten tanpa edit core
- [ ] 3 core plugins berfungsi penuh dan terdokumentasi
- [ ] Tidak ada regresi pada Phase 1–3
- [ ] Response time frontend tetap < 300ms dengan semua plugin dimuat
- [ ] Full regression test suite Phase 1–4 hijau

---

## 3. Improvements Phase 1–3 yang Diimplementasi di Phase 4

Berikut adalah backlog improvement dari phase sebelumnya yang **dijadwalkan di Phase 4**.
Setiap improvement memiliki slot implementasi yang direkomendasikan (lihat Seksi 5).

### [HIGH] IMP-01 — Admin Activity Audit Log
**Dari:** Phase 1 (Foundation & Auth)
**Effort:** 2 hari

Catat semua aksi penting admin ke tabel `audit_logs`.
Plugin system juga memerlukan audit log ini (setiap plugin activation/deactivation dicatat).

**Database schema:**
```sql
CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    action          VARCHAR(100) NOT NULL,      -- 'created', 'updated', 'deleted', 'published', 'plugin.activated'
    auditable_type  VARCHAR(255) NOT NULL,      -- 'Page', 'Plugin', 'Theme', 'Menu'
    auditable_id    BIGINT UNSIGNED NULLABLE,
    old_values      JSON NULLABLE,
    new_values      JSON NULLABLE,
    ip_address      VARCHAR(45) NULLABLE,
    user_agent      VARCHAR(500) NULLABLE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_auditable (auditable_type, auditable_id),
    INDEX idx_audit_created (created_at)
);
```

**Implementasi:** Laravel Observer pada model utama + Event Listener untuk aksi manual.
```php
// app/Observers/PageObserver.php
class PageObserver {
    public function updated(Page $page): void {
        AuditLog::record('updated', $page, $page->getOriginal(), $page->getChanges());
    }
}
// Register di AppServiceProvider: Page::observe(PageObserver::class);
```

**Tampilan admin:** Tabel di `admin/audit-logs` dengan filter: user, action type, date range.
**Slot implementasi:** Sebelum STEP 0 (prasyarat, karena STEP 8 plugin security butuh ini).

---

### [HIGH] IMP-02 — Content Revision History
**Dari:** Phase 2 (Content Management)
**Effort:** 4 hari

Simpan snapshot konten setiap kali halaman disave. Admin dapat melihat riwayat
dan restore ke versi sebelumnya.

**Database schema:**
```sql
CREATE TABLE page_revisions (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id          BIGINT UNSIGNED NOT NULL,
    revision_number  INT UNSIGNED NOT NULL DEFAULT 1,
    content_snapshot JSON NOT NULL,             -- full blocks JSON snapshot
    meta_snapshot    JSON NULLABLE,             -- title, slug, SEO fields
    created_by       BIGINT UNSIGNED NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id)    REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_revision_page (page_id, revision_number)
);
```

**Implementasi:**
```php
// Simpan revision di PageService::update() sebelum apply perubahan
public function update(Page $page, array $data): Page {
    $this->saveRevision($page);            // <-- tambahkan baris ini
    $page->update($data);
    return $page;
}

private function saveRevision(Page $page): void {
    $lastRevision = $page->revisions()->max('revision_number') ?? 0;
    PageRevision::create([
        'page_id'          => $page->id,
        'revision_number'  => $lastRevision + 1,
        'content_snapshot' => $page->blocks,
        'meta_snapshot'    => $page->only(['title', 'slug', 'meta_title', 'meta_description']),
        'created_by'       => auth()->id(),
    ]);
    // Bersihkan revision lebih dari 20 per halaman
    $page->revisions()->orderBy('created_at')->skip(20)->take(PHP_INT_MAX)->delete();
}
```

**UI Admin:** Tab "Revisi" di halaman edit — daftar revisi dengan timestamp dan author,
tombol "Preview" dan "Restore" per revisi. Restore membuka confirm dialog.
**Slot implementasi:** Di antara STEP 4 dan STEP 5.

---

### [MEDIUM] IMP-03 — Content Scheduling (Publish At)
**Dari:** Phase 2 (Content Management)
**Effort:** 3 hari

Tambahkan kemampuan menjadwalkan publish halaman di masa depan.

**Migration:**
```php
// Tambah kolom pada tabel pages
$table->timestamp('publish_at')->nullable()->after('status');
// Status logic: 'draft' | 'published' | 'scheduled'
```

**Scheduler:**
```php
// app/Console/Commands/PublishScheduledPages.php
// Jalankan setiap menit via Laravel Scheduler
$schedule->command('pages:publish-scheduled')->everyMinute();

// Command logic:
Page::where('status', 'scheduled')
    ->where('publish_at', '<=', now())
    ->update(['status' => 'published', 'publish_at' => null]);
```

**UI:** Date-time picker di form halaman. Saat pilih tanggal masa depan,
status otomatis berubah ke 'scheduled' dan tampil badge kuning "Terjadwal: DD/MM/YYYY HH:MM".
**Slot implementasi:** Di antara STEP 4 dan STEP 5 (bersamaan dengan IMP-02).

---

### [MEDIUM] IMP-04 — Duplicate Page
**Dari:** Phase 2 (Content Management)
**Effort:** 1 hari

Tombol "Duplikat" pada list halaman untuk clone konten beserta semua blok.

**Implementasi:**
```php
// PageService::duplicate(Page $original): Page
public function duplicate(Page $page): Page {
    $copy = $page->replicate();
    $copy->title  = $page->title . ' (Copy)';
    $copy->slug   = $page->slug . '-copy-' . time();
    $copy->status = 'draft';
    $copy->publish_at = null;
    $copy->save();
    // Clone relasi blocks/blok jika ada relasi terpisah
    $page->blocks()->get()->each(fn($b) => $b->replicate()->fill(['page_id' => $copy->id])->save());
    return $copy;
}
```

**Route:** `POST /admin/pages/{page}/duplicate`
**Slot implementasi:** Awal Phase 4, sebelum STEP 0 (quick win 1 hari).

---

### [MEDIUM] IMP-05 — Theme Export & Import (ZIP)
**Dari:** Phase 3 (Theme System)
**Effort:** 5 hari

Export tema aktif beserta semua konfigurasinya (tokens, widget config) sebagai ZIP.
Import dan install tema baru dari file ZIP di admin.

**Export flow:**
```
Admin klik "Export Tema"
→ Zip direktori themes/{active_theme}/
→ Tambahkan theme_config.json (design tokens + widget settings dari DB)
→ Stream download ke browser
```

**Import flow:**
```
Admin upload ZIP
→ Validate: ada theme.json manifest? Struktur folder benar?
→ Extract ke themes/{slug}/
→ Insert/update record di themes table
→ Redirect ke theme list dengan flash success
```

**Validasi manifest minimum:**
```json
{
    "name": "string (required)",
    "slug": "string (required, unique)",
    "version": "string (required, semver)",
    "author": "string",
    "min_cms_version": "string"
}
```

**Security:** Validasi MIME type ZIP, block PHP file upload di dalam ZIP,
scan isi ZIP sebelum extract (max size: 50MB).
**Slot implementasi:** STEP 1–2 (bersamaan dengan plugin loader, shared ZIP handling logic).

---

### [MEDIUM] IMP-06 — Extended Design Tokens
**Dari:** Phase 3 (Theme System)
**Effort:** 3 hari

Tambahkan token spacing, border-radius, dan box-shadow ke theme customizer.
Ekspos sebagai CSS custom properties.

**Token tambahan yang diekspos:**
```css
:root {
    /* Spacing scale */
    --bp-space-xs:  4px;
    --bp-space-sm:  8px;
    --bp-space-md:  16px;
    --bp-space-lg:  24px;
    --bp-space-xl:  32px;
    --bp-space-2xl: 48px;

    /* Border radius */
    --bp-radius-sm:   4px;
    --bp-radius-md:   8px;
    --bp-radius-lg:   16px;
    --bp-radius-full: 9999px;

    /* Box shadow */
    --bp-shadow-sm:  0 1px 3px rgba(0,0,0,.12);
    --bp-shadow-md:  0 4px 6px rgba(0,0,0,.1);
    --bp-shadow-lg:  0 10px 15px rgba(0,0,0,.1);
    --bp-shadow-xl:  0 20px 25px rgba(0,0,0,.1);

    /* Font sizes */
    --bp-text-sm:   0.875rem;
    --bp-text-base: 1rem;
    --bp-text-lg:   1.125rem;
    --bp-text-xl:   1.25rem;
    --bp-text-2xl:  1.5rem;
    --bp-text-3xl:  1.875rem;
    --bp-text-4xl:  2.25rem;
}
```

**DB schema tambahan:** Kolom baru di `theme_settings` atau kolom JSON `extended_tokens`
di tabel `themes`.
**Slot implementasi:** STEP 3 (bersamaan dengan hook system yang mungkin filter tokens).

---

### [MEDIUM] IMP-07 — Google Fonts Integration
**Dari:** Phase 3 (Theme System)
**Effort:** 3 hari

Font picker di theme customizer yang menampilkan daftar Google Fonts.
Pilihan font langsung di-preview, dan generate `@import` di `<head>`.

**Implementasi:**
```php
// Cache daftar font dari Google Fonts API selama 24 jam
// GET https://www.googleapis.com/webfonts/v1/webfonts?key={API_KEY}&sort=popularity

public function getFontList(): array {
    return Cache::remember('google_fonts_list', now()->addDay(), function () {
        $response = Http::get('https://www.googleapis.com/webfonts/v1/webfonts', [
            'key'  => config('services.google_fonts.key'),
            'sort' => 'popularity',
        ]);
        return $response->json('items', []);
    });
}
```

**Blade output:**
```html
<!-- Di layout <head>, setelah tema stylesheet -->
@if($activeFontFamily)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($activeFontFamily) }}:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: '{{ $activeFontFamily }}', sans-serif; }</style>
@endif
```

**UI:** Dropdown searchable di customizer sidebar. Preview teks "The quick brown fox"
berubah saat pilih font. Tombol Save apply ke seluruh website.
**Fallback:** Jika Google Fonts API tidak available, gunakan system fonts stack.
**Slot implementasi:** STEP 3–4 (setelah IMP-06 extended tokens selesai).

---

### [LOW] IMP-08 — Child Theme Support
**Dari:** Phase 3 (Theme System)
**Effort:** 5 hari | **Priority: LOW — implementasi hanya jika ada waktu sisa**

Child theme yang mewarisi parent. Field `parent_theme` di manifest.
Child dapat override template files tanpa mengubah parent.

**Slot implementasi:** Setelah STEP 9 Release Gate, sebagai bonus jika timeline memungkinkan.

---

## 4. Jadwal Implementasi Lengkap Phase 4

Urutan aktual yang harus diikuti (gabungan Phase 4 steps + improvements):

```
PEKAN 0 (sebelum mulai)  → Commit Phase 1–3 ke branch existing, buat branch feature/phase-4-plugin-system
PRE-STEP  (1–2 hari)     → IMP-04: Duplicate Page [quick win]
PRE-STEP  (2 hari)       → IMP-01: Admin Audit Log [prasyarat untuk STEP 8]

STEP 0    (5 hari)       → Baseline characterization tests + Architecture design
STEP 1    (5 hari)       → Plugin Registry & Discovery
STEP 1.5  (5 hari)       → IMP-05: Theme Export & Import [shared ZIP logic dengan STEP 1]
STEP 2    (5 hari)       → Plugin Loader & Lifecycle
STEP 3    (7 hari)       → Hook & Filter Event System
STEP 3.5  (3 hari)       → IMP-06: Extended Design Tokens [tema hooks sudah ada]
STEP 3.6  (3 hari)       → IMP-07: Google Fonts Integration
STEP 4    (5 hari)       → Plugin Admin Interface
STEP 4.5  (4 hari)       → IMP-02: Content Revision History
STEP 4.6  (3 hari)       → IMP-03: Content Scheduling
STEP 6    (7 hari)       → Core Plugin: Contact Form Builder
STEP 7    (5 hari)       → Core Plugin: Analytics Dashboard
STEP 5    (7 hari)       → Core Plugin: SEO Manager  [dipindah ke setelah STEP 7]
STEP 8    (5 hari)       → Plugin Security & Sandboxing
STEP 9    (5 hari)       → Phase 4 Release Gate
[BONUS]                  → IMP-08: Child Theme Support (jika ada waktu)
```

**Total estimasi:** ~80–85 hari kerja (~11–12 minggu @ 7–8 jam/hari)

---

## 5. Phase 4 Steps — Detail Teknis

### STEP 0 — Baseline & Architecture Design
**Minggu 1 | Durasi: 5 hari**

**Deliverables:**
- Architecture Decision Record (ADR) untuk plugin system
- Database migration: tabel `plugins`
- Plugin manifest format spec
- Baseline snapshot tests (karakterisasi behavior existing Phase 1–3)

**Database migration:**
```php
Schema::create('plugins', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('version');
    $table->string('author')->nullable();
    $table->text('description')->nullable();
    $table->boolean('is_active')->default(false);
    $table->json('config')->nullable();
    $table->timestamp('installed_at')->nullable();
    $table->timestamp('activated_at')->nullable();
    $table->timestamps();
});
```

**Plugin manifest `plugin.json`:**
```json
{
    "name": "SEO Manager",
    "slug": "seo-manager",
    "version": "1.0.0",
    "author": "Bintan Prestige",
    "description": "Advanced SEO management for Bintan Prestige CMS",
    "min_cms_version": "4.0.0",
    "service_provider": "App\\Plugins\\SeoManager\\SeoManagerServiceProvider",
    "requires": []
}
```

**Approval required sebelum lanjut:** Schema migration harus di-review owner.

---

### STEP 1 — Plugin Registry & Discovery
**Minggu 2 | Durasi: 5 hari**

**Deliverables:**
- `App\Services\Plugin\PluginRegistry` service class
- Scan direktori `app/Plugins/{PluginName}/plugin.json`
- Sinkronisasi direktori ↔ database `plugins`
- Deteksi plugin baru (installed not in DB), removed (in DB not in dir), updated (version mismatch)

**Directory structure:**
```
app/Plugins/
├── SeoManager/
│   ├── plugin.json
│   ├── SeoManagerServiceProvider.php
│   ├── Http/Controllers/
│   ├── Models/
│   └── resources/views/
├── ContactForm/
│   ├── plugin.json
│   └── ContactFormServiceProvider.php
└── Analytics/
    ├── plugin.json
    └── AnalyticsServiceProvider.php
```

---

### STEP 2 — Plugin Loader & Lifecycle
**Minggu 3 | Durasi: 5 hari**

**Deliverables:**
- `App\Services\Plugin\PluginManager` (activate, deactivate, uninstall)
- Base class: `App\Plugins\PluginServiceProvider`
- Interface: `App\Contracts\PluginLifecycle`
- Boot order: topological sort by dependency graph
- Auto-register active plugin providers di `AppServiceProvider::register()`

**Lifecycle interface:**
```php
interface PluginLifecycle {
    public function onInstall(): void;
    public function onActivate(): void;
    public function onDeactivate(): void;
    public function onUninstall(): void;
}
```

**Loading di AppServiceProvider:**
```php
public function register(): void {
    $activePlugins = Cache::remember('active_plugins', 3600, fn() =>
        Plugin::where('is_active', true)->pluck('slug')
    );
    foreach ($activePlugins as $slug) {
        // Load manifest, resolve provider class, register
        $provider = $this->resolveProvider($slug);
        $this->app->register($provider);
    }
}
```

---

### STEP 3 — Hook & Filter Event System
**Minggu 4 | Durasi: 7 hari**

**Deliverables:**
- `App\Support\HookManager` class
- `App\Facades\CmsHooks` facade
- Core action hooks terdaftar
- Core filter hooks terdaftar

**API yang diekspos:**
```php
// Action hooks (seperti WordPress do_action)
CmsHooks::addAction('page.render', function(Page $page) {
    // Plugin dapat bereaksi terhadap event
}, priority: 10);
CmsHooks::doAction('page.render', $page);

// Filter hooks (seperti WordPress apply_filters)
CmsHooks::addFilter('page.content', function(string $content) {
    return '<div class="plugin-wrapper">' . $content . '</div>';
}, priority: 10);
$content = CmsHooks::applyFilters('page.content', $rawContent);
```

**Core action hooks yang harus ada:**
```
cms.init            → setelah CMS boot
admin.loaded        → setelah admin dashboard dimuat
page.render         → sebelum halaman dirender
block.render        → sebelum setiap blok dirender
menu.render         → sebelum menu dirender
plugin.activated    → setelah plugin diaktifkan
plugin.deactivated  → setelah plugin dinonaktifkan
```

**Core filter hooks yang harus ada:**
```
page.content        → filter output konten halaman
block.output        → filter output tiap blok
seo.meta            → filter meta tags array
nav.items           → filter array item navigasi
theme.tokens        → filter array design tokens
```

---

### STEP 4 — Plugin Admin Interface
**Minggu 5 | Durasi: 5 hari**

**Deliverables:**
- Route group: `admin/plugins`
- Views: `admin.plugins.index`, `admin.plugins.show`
- Blade komponen: toggle aktif/nonaktif, modal konfirmasi delete
- Plugin Settings Page API:
  ```php
  // Di PluginServiceProvider plugin:
  public function boot(): void {
      CmsHooks::addAction('admin.menu', function() {
          AdminMenu::add('SEO Manager', 'admin.plugins.seo-manager.settings', 'cog-icon');
      });
  }
  ```
- Sidebar admin otomatis menampilkan menu dari plugin aktif

---

### STEP 5 — Core Plugin: SEO Manager
**Minggu 6–7 | Durasi: 7 hari**

**Deliverables:**
- Dynamic XML Sitemap di `/sitemap.xml` (auto-include semua published pages)
- Robots.txt editable dari admin (simpan ke storage, serve via route)
- Open Graph configurator: global defaults + per-page override (sudah ada OG basic, ini extends)
- JSON-LD templates: Organization, WebPage, BreadcrumbList
- Redirect manager: tabel `redirects` (from_url, to_url, status_code 301/302)

**Migration redirects:**
```php
Schema::create('redirects', function (Blueprint $table) {
    $table->id();
    $table->string('from_url')->unique();
    $table->string('to_url');
    $table->smallInteger('status_code')->default(301);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index('from_url');
});
```

---

### STEP 6 — Core Plugin: Contact Form Builder
**Minggu 7–8 | Durasi: 7 hari**

**Deliverables:**
- Form builder UI (field types: text, email, phone, select, textarea, checkbox, file)
- Form embed: block type baru `contact_form` di page block editor
- Submissions inbox (sortable, filterable, mark read/unread)
- Email notification ke admin (via Laravel Mail + queue)
- Honeypot field anti-spam (hidden field, bila terisi = bot)

**Migrations:**
```php
// form_definitions: { id, name, slug, fields JSON, settings JSON, created_at }
// form_submissions: { id, form_id, data JSON, is_read, ip_address, created_at }
```

**PENTING:** Block `contact_form` harus mengikuti block schema existing.
Inspect `blocks` table schema sebelum menambah type baru.

---

### STEP 7 — Core Plugin: Analytics Dashboard
**Minggu 9 | Durasi: 5 hari**

**Deliverables:**
- Page view tracking via middleware (server-side, tanpa JavaScript external)
- Unique visitor: hash SHA256(IP + User-Agent) per hari
- Admin dashboard: chart views (Chart.js, 30 hari terakhir)
- Top 10 halaman terpopuler
- Export CSV

**Migration:**
```php
Schema::create('page_views', function (Blueprint $table) {
    $table->id();
    $table->foreignId('page_id')->constrained()->cascadeOnDelete();
    $table->string('visitor_hash', 64);        // SHA256(ip+ua)
    $table->string('referrer', 500)->nullable();
    $table->string('country_code', 2)->nullable();
    $table->date('viewed_date');               // untuk daily aggregation
    $table->timestamps();
    $table->index(['page_id', 'viewed_date']);
    $table->index('viewed_date');
});
```

**Performance:** Jangan query `page_views` raw untuk chart — buat aggregate harian
ke tabel `page_view_daily_stats` via scheduled job (jam 00:05 setiap hari).

---

### STEP 8 — Plugin Security & Sandboxing
**Minggu 10 | Durasi: 5 hari**

**Deliverables:**
- PHP token scanner sebelum aktivasi plugin (cari fungsi berbahaya)
- Blocklist: `exec`, `system`, `passthru`, `eval`, `shell_exec`, `proc_open`, `popen`
- Plugin permission scopes (declare di manifest, validate di aktivasi)
- Exception isolation: plugin error tidak crash CMS (try-catch di PluginManager)
- Integrasi dengan IMP-01 Audit Log: setiap aktivasi/deaktivasi tercatat

**Scanner sederhana:**
```php
$blocklist = ['exec(', 'system(', 'passthru(', 'eval(', 'shell_exec(', 'proc_open('];
$phpFiles  = glob("app/Plugins/{$slug}/**/*.php", GLOB_BRACE);
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    foreach ($blocklist as $fn) {
        if (str_contains($content, $fn)) {
            throw new PluginSecurityException("Blocked function '{$fn}' found in {$file}");
        }
    }
}
```

---

### STEP 9 — Phase 4 Release Gate
**Minggu 11–12 | Durasi: 5 hari**

**Checklist sebelum merge ke main / tag release:**
- [ ] `php artisan test` → semua hijau (Phase 1–4 coverage)
- [ ] `./vendor/bin/phpstan analyse` → tidak ada error level 5
- [ ] Plugin SEO Manager: sitemap valid di Google Rich Results Test
- [ ] Plugin Contact Form: form submit → email terkirim → submission tercatat
- [ ] Plugin Analytics: page view tercatat → chart tampil di dashboard
- [ ] Semua IMP-01 s/d IMP-07 tercentang done
- [ ] Performance: response time frontend < 300ms (benchmark dengan semua plugin aktif)
- [ ] Audit log: setiap aksi admin tercatat dengan benar
- [ ] Git tag: `git tag v4.0.0 && git push origin v4.0.0`
- [ ] Update AGENTS.md: Phase 4 → status done

---

## 6. Aturan Wajib Phase 4

Selain aturan global di AGENTS.md, tambahan untuk Phase 4:

1. **Tidak ada query di dalam plugin boot()** — cache semua config saat load
2. **Setiap plugin WAJIB punya try-catch** di method-method yang bisa throw exception
3. **Tidak ada hardcode path** di plugin — gunakan `app_path()`, `storage_path()`, `public_path()`
4. **Semua migration plugin** diberi prefix: `create_plugin_{name}_*` agar mudah diidentifikasi
5. **Setiap plugin wajib punya** `uninstall()` method yang membersihkan data miliknya
6. **PHP dangerous function check** wajib dijalankan sebelum `onActivate()` dipanggil
7. **Inspect existing files** sebelum edit apapun — jangan asumsi struktur file

---

## 7. Database Schema Quick Reference (Phase 4)

| Tabel | Dibuat di | Tujuan |
|-------|-----------|--------|
| `plugins` | STEP 0 | Registry semua plugin |
| `audit_logs` | IMP-01 (Pre-STEP) | Log aksi admin |
| `page_revisions` | IMP-02 (STEP 4.5) | History revisi halaman |
| `redirects` | STEP 5 | URL redirect manager |
| `form_definitions` | STEP 6 | Definisi contact form |
| `form_submissions` | STEP 6 | Data submission form |
| `page_views` | STEP 7 | Raw page view tracking |
| `page_view_daily_stats` | STEP 7 | Aggregated stats harian |

**Alter table (existing):**
| Tabel | Kolom ditambah | Dibuat di |
|-------|---------------|-----------|
| `pages` | `publish_at` timestamp | IMP-03 |

---

## 8. Approval Gates — Wajib Minta Persetujuan Owner

Hentikan pekerjaan dan tunggu approval sebelum:
- [ ] Menjalankan migration baru (`php artisan migrate`)
- [ ] Membuat tabel baru (review schema dulu)
- [ ] Menambah kolom ke tabel existing (`pages`, `themes`, dll.)
- [ ] Menginstall package Composer baru
- [ ] Mengubah routing yang sudah ada
- [ ] Mengubah AppServiceProvider atau Kernel middleware

---

## 9. Report Template untuk Setiap STEP

Gunakan format ini setelah setiap step selesai:

```
## Phase 4 STEP [X] Complete: [Title]

### Changed
- `path/to/file.php` — deskripsi perubahan

### Improvements Completed (jika ada)
- [IMP-0X] Nama improvement — done

### Impact
- DB: none | migration: [nama file]
- Routes: none | added: [route]
- Frontend: none | [section] sekarang menampilkan [X]
- Performance: [ukuran sebelum vs sesudah jika relevan]

### Rollback
`git revert [hash]` atau rollback migration: `php artisan migrate:rollback --step=1`

### Next
[STEP berikutnya yang harus dikerjakan]
```

---

## 10. Cara Memulai Sesi Claude Code Phase 4

Di awal setiap sesi Claude Code, kirim prompt ini:

```
Read ai/skills/phase4-plugin-module-skill.md first.
Then check current git branch.
Then list files changed since last commit.
Current task: [sebutkan STEP atau IMP yang dikerjakan]
```

---

*File ini dibuat dari `bintan_prestige_cms_plan.json` v2.0 — 19 Juni 2026*
*Update file ini setiap STEP selesai dengan mencentang checklist yang relevan.*
