# B5 — Per-Field Validation Resolver

> **Task:** B5 — Per-Field Validation Resolver
> **Status:** ✅ DONE — 2026-07-01
> **Branch:** `feature/phase-6-a1-debt-clearing`
> **Approval gate:** — (no schema change)
> **Skills:** backend-skill

---

## What was built

### `FieldValidationResolver` (`app/Support/FieldValidationResolver.php`)

Service class yang membangun Laravel validation rules secara dinamis untuk seluruh field dalam sebuah ContentType. Dipanggil dari `StoreContentEntryRequest` dan `UpdateContentEntryRequest` saat request masuk.

#### Method utama

**`resolveForContentType(ContentType $contentType): array`**

Mengambil semua FieldGroups beserta Fields-nya via eager load (2 query, tidak N+1), lalu memanggil `resolveForField()` per field. Return value adalah array keyed oleh `'data.field_key'` — langsung di-merge ke array rules FormRequest.

**`resolveForField(Field $field, string $prefix = 'data'): array`**

Mendelegasikan berdasarkan `$field->type`:

| Tipe | Logika Resolusi |
|------|-----------------|
| `gallery` | `data.photos` → array; `data.photos.*` → nullable integer min:1 (media ID) |
| `checkbox` | `data.amenities` → array; `data.amenities.*` → nullable string max:255 |
| `relationship` | `data.entries` → array; `data.entries.*` → nullable integer min:1 (entry ID) |
| `repeater` | `data.rows` → array; `data.rows.*` → nullable array |
| `datetime` | Spesial: katalog punya dua `date_format` yang saling konflik → resolver normalisasi ke `'date'` (flexible) |
| semua lainnya | `catalogRules()` — baca dari katalog + resolve placeholder |

**`catalogRules(Field $field): array`** (private)

Membaca `validation_rules` dari `config/field-types.php` via `FieldTypeRegistry::get()`.

Placeholder `{setting_key}` dalam rules (contoh: `max:{maxlength}`) diselesaikan dari `$field->settings`:
- Setting tersedia → nilai disubstitusi, rule dimasukkan: `max:100`
- Setting tidak ada → rule dengan placeholder tersebut **dibuang** (tidak diganti dengan string kosong)

Contoh: field `text` tanpa setting `maxlength` → rule `max:{maxlength}` dibuang → result: `['nullable', 'string']` (bukan `max:` kosong).

**`applyRequired(Field $field, array $rules): array`** (private)

- `is_required = true` → hapus `'nullable'`, prepend `'required'`
- `is_required = false` → pastikan `'nullable'` ada di depan

### Integrasi ke FormRequests

**`StoreContentEntryRequest::rules()`** dan **`UpdateContentEntryRequest::rules()`** dimodifikasi:

```php
// Sebelum B5:
'data'   => ['nullable', 'array'],
'data.*' => ['nullable'],  // generik, tidak type-aware

// Sesudah B5:
'data'   => ['nullable', 'array'],
// + $fieldRules dari resolver, contoh:
'data.contact_email' => ['nullable', 'string', 'email:rfc,dns', 'max:255'],
'data.star_rating'   => ['nullable', 'numeric', 'min:1', 'max:5'],
'data.photos'        => ['nullable', 'array'],
'data.photos.*'      => ['nullable', 'integer', 'min:1'],
```

`data.*` generik dihapus. Field yang tidak terdefinisi dalam sistem (orphaned JSON key dari field yang sudah dihapus) tidak punya rule → lolos tanpa validasi → data lama tidak rusak.

### Keputusan Desain

**Placeholder hanya dibuang jika setting tidak dikonfigurasi**

Rule `max:{maxlength}` dibuang seluruhnya (bukan diganti string kosong) jika `maxlength` tidak ada di `$field->settings`. Ini berarti field tanpa batas panjang eksplisit tidak mendapat rule `max:` sama sekali — lebih aman daripada `max:` tanpa nilai.

**Datetime dinormalisasi ke `'date'`**

Katalog mendefinisikan dua `date_format` untuk `datetime` (`Y-m-d H:i:s` dan `Y-m-d H:i`). Laravel menerapkan keduanya sekaligus (AND), bukan OR — sehingga nilai apapun akan selalu gagal salah satu. Resolver mengganti keduanya dengan rule `'date'` yang fleksibel.

**Orphaned data keys tidak diblokir**

Entry yang dibuat sebelum field dihapus tetap menyimpan datanya di JSON. Karena tidak ada rule untuk key yang tidak dikenal, data ini lolos validasi — tidak rusak, tidak memblokir update.

**Eager loading di resolveForContentType**

```php
$contentType->fieldGroups()
    ->with(['fields' => fn ($q) => $q->orderBy('sort_order')])
    ->orderBy('sort_order')
    ->get();
```

Hanya 2 query per FormRequest — tidak N+1 walau content type punya puluhan field group.

---

## Report (AGENTS.md §11)

### Changed
- `app/Support/FieldValidationResolver.php` — **baru** (`final class`, 2 public methods + 4 private helpers)
- `app/Http/Requests/Admin/StoreContentEntryRequest.php` — tambah `use FieldValidationResolver`; `rules()` kini memanggil resolver + merge `$fieldRules`; `'data.*'` generik dihapus
- `app/Http/Requests/Admin/UpdateContentEntryRequest.php` — perubahan yang sama
- `tests/Feature/Phase6/B5FieldValidationResolverTest.php` — **baru** (14 tests)

### Impact
- DB: tidak ada perubahan.
- Routes: tidak ada perubahan.
- Frontend: tidak ada perubahan tampilan. Entry form yang sudah ada langsung mendapat validasi per-field.
- Security: Submission dengan nilai yang melanggar tipe field (email invalid, angka di luar range, teks terlalu panjang) kini ditolak di layer FormRequest, bukan hanya disimpan mentah ke JSON. Field `is_required = true` kini benar-benar divalidasi server-side.

### Verification
- B5 tests: **14/14 pass**, 24 assertions.
  - 10 unit tests: resolver output per tipe (text/email/number/datetime/gallery/checkbox/required)
  - 4 integration tests: FormRequest enforcement (email invalid, text maxlength, required missing, optional null)
- Full suite: **715/715 pass**, 3855 assertions. PHPStan level 5: **0 errors**.

### Rollback
```bash
git revert <commit-hash>
# FieldValidationResolver.php akan terhapus
# FormRequests kembali ke 'data.*' => ['nullable']
```

### Next
- **B6 — Sidecar Index** (`content_entry_index` table): proyeksikan field `is_filterable = true` ke tabel terpisah untuk kueri cepat tanpa scan JSON.
