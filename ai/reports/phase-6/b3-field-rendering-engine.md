# B3 — Field Rendering Engine

> **Task:** B3 — Field rendering engine (admin form partials)
> **Status:** ✅ DONE — 2026-07-01
> **Commit:** `0040ea5`
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** — (no schema change)
> **Skills:** frontend-design-skill · backend-skill

---

## What was built

### `<x-admin.field-input>` — Anonymous Blade Component

**File:** `resources/views/components/admin/field-input.blade.php`

Komponen dispatcher utama. Menerima `$field` (model Field), `$value` (nilai tersimpan), dan `$namePrefix` (prefix untuk nama input, default `data`). Otomatis memilih partial berdasarkan `$field->type` dan me-render:

1. Label (+ tanda `*` merah jika `is_required`)
2. Instructions (teks bantuan di bawah label)
3. Type-specific partial (`fields.{type}`)
4. Error message dari ValidationMessageBag (dengan fallback `new MessageBag()` untuk konteks render-only/test)

**Pemanggilan:**
```blade
<x-admin.field-input
    :field="$field"
    :value="$entry->fieldValue($field->key)"
    name-prefix="data"
/>
```

### 18 Type Partials (`resources/views/fields/`)

Setiap file menangani: rendering input, binding value via `old()`, error state (`border-red-400`), dan atribut settings dari `$field->settings`.

| File | Tipe Input | Fitur Khusus |
|------|-----------|--------------|
| `text.blade.php` | `<input type="text">` | placeholder, maxlength dari settings |
| `textarea.blade.php` | `<textarea>` | rows, maxlength dari settings |
| `richtext.blade.php` | contenteditable + hidden input | Alpine.js binding; editor penuh di B4 |
| `number.blade.php` | `<input type="number">` | min, max, step dari settings |
| `email.blade.php` | `<input type="email">` | placeholder dari settings |
| `url.blade.php` | `<input type="url">` | font-mono, placeholder dari settings |
| `toggle.blade.php` | hidden(0) + checkbox(1) | hidden input fallback agar `false` tetap terkirim saat unchecked |
| `select.blade.php` | `<select>` | opsi dari `$field->settings['options']`, support `multiple`; warning jika kosong |
| `radio.blade.php` | radio buttons | layout horizontal/vertical dari settings; warning jika kosong |
| `checkbox.blade.php` | multi-checkbox | hidden fallback array; warning jika kosong |
| `date.blade.php` | `<input type="date">` | min/max dari settings |
| `datetime.blade.php` | `<input type="datetime-local">` | konversi format `Y-m-d H:i:s` → `Y-m-d\TH:i` via `\DateTime` |
| `image.blade.php` | hidden + Alpine preview | dispatch `open-media-library`; preview image; tombol Remove |
| `gallery.blade.php` | hidden array + Alpine | multi-image picker; thumbnail grid; hapus individual |
| `file.blade.php` | hidden + Alpine preview | nama file + link; dispatch `open-media-library` |
| `relationship.blade.php` | hidden + Alpine tags | ID picker; warning jika content_type belum dikonfigurasi |
| `color.blade.php` | color picker + text hex input | Alpine sync antar input; preview swatch |
| `repeater.blade.php` | Alpine row list | `Add Row` / hapus baris; sub-fields dari `$field->settings['sub_fields']` |

### Keputusan Teknis Penting

**`$errors` guard:**
```php
$errorBag = $errors ?? new \Illuminate\Support\MessageBag();
```
`$errors` hanya tersedia dalam request context. Di luar request (unit test blade rendering), variabel ini tidak ada. Guard ini mencegah `Undefined variable $errors` tanpa memerlukan trait atau presenter tambahan.

**`ConvertEmptyStringsToNull` awareness:**
Partial `toggle` dan `checkbox` menggunakan hidden input sebagai fallback. Nilai `''` bisa berubah jadi `null` oleh middleware. Ini ditangani di `StoreContentEntryRequest::prepareForValidation` di B4 (filter `!== null && !== ''`).

**Media types (image/gallery/file):**
Menggunakan Alpine.js + `$dispatch('open-media-library', {...})` — event yang sama dengan pattern media picker di Phase 5 Page Builder. Full integration dengan modal Media Library terjadi saat entry form terhubung di B4.

**Relationship:**
Untuk B3, relasi menggunakan input ID manual. Full entry picker dengan search/autocomplete diimplementasi di B4+.

---

## Report (AGENTS.md §11)

### Changed
- `resources/views/components/admin/field-input.blade.php` — **baru** (anonymous component dispatcher)
- `resources/views/fields/text.blade.php` — **baru**
- `resources/views/fields/textarea.blade.php` — **baru**
- `resources/views/fields/richtext.blade.php` — **baru**
- `resources/views/fields/number.blade.php` — **baru**
- `resources/views/fields/email.blade.php` — **baru**
- `resources/views/fields/url.blade.php` — **baru**
- `resources/views/fields/toggle.blade.php` — **baru**
- `resources/views/fields/select.blade.php` — **baru**
- `resources/views/fields/radio.blade.php` — **baru**
- `resources/views/fields/checkbox.blade.php` — **baru**
- `resources/views/fields/date.blade.php` — **baru**
- `resources/views/fields/datetime.blade.php` — **baru**
- `resources/views/fields/image.blade.php` — **baru**
- `resources/views/fields/gallery.blade.php` — **baru**
- `resources/views/fields/file.blade.php` — **baru**
- `resources/views/fields/relationship.blade.php` — **baru**
- `resources/views/fields/color.blade.php` — **baru**
- `resources/views/fields/repeater.blade.php` — **baru**
- `tests/Feature/Phase6/B3FieldRenderingTest.php` — **baru** (14 tests)

### Impact
- DB: tidak ada perubahan.
- Routes: tidak ada perubahan.
- Frontend: komponen `<x-admin.field-input>` siap dipakai di semua form entry (B4+). Tidak ada halaman yang berubah tampilan di fase ini.
- Security: tidak ada input user baru. Rendering-only layer.

### Verification
- B3 tests: **14/14 pass**, 57 assertions. Covers: semua 18 tipe render tanpa error, label required, instructions, name prefix, spesifik per tipe (text value, select options, toggle pair, color, datetime format, gallery button, repeater empty state, relationship warnings).
- Full suite: **687/687 pass**, 3791 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
git revert 0040ea5
# Hapus resources/views/fields/ dan resources/views/components/admin/field-input.blade.php
```

### Next
- **B4 — Content Entries module** (⚠️ schema gate: tabel baru `content_entries`).
