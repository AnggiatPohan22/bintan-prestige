# STEP 11C - FAQ Index UI Upgrade

## Tujuan

Merapikan halaman `FAQs` agar lebih konsisten dengan Admin Design System dan lebih nyaman dibaca, khususnya untuk question/answer yang panjang.

## File yang Berubah

- `resources/views/backend/faqs/index.blade.php`

## File yang Terkait

- `resources/css/admin.css`
- `app/Http/Controllers/Admin/FaqController.php`
- `routes/admin.php`
- `app/Models/Faq.php`

## Perubahan UI

- Halaman memakai `admin-page`.
- Header memakai `admin-page-header`, `admin-page-title`, dan `admin-page-subtitle`.
- Tombol create memakai `admin-btn-primary`.
- FAQ List dibungkus dengan `admin-card`.
- Table memakai reusable admin table class.
- Question dibuat sebagai teks utama.
- Answer ditampilkan sebagai preview dengan `Str::limit($faq->answer, 160)`.
- Status active/inactive memakai `admin-badge-success` dan `admin-badge-warning`.
- Sort order tetap terlihat.
- Edit memakai `admin-btn-soft`.
- Delete memakai `admin-btn-danger`.
- Empty state memakai `admin-empty-state`.

## Data yang Ditampilkan

- Question.
- Preview answer.
- Status active/inactive.
- Sort order.
- Action edit/delete.

## Dampak ke Database

Tidak ada perubahan database.

- Tidak ada migration.
- Tidak ada perubahan table FAQs.
- Tidak ada perubahan data FAQ.
- Preview answer hanya visual, data asli tidak dipotong.

## Dampak ke Model

Tidak ada perubahan model.

- `Faq` tetap memakai field existing.
- Tidak ada relationship baru.
- Tidak ada accessor/mutator baru.

## Dampak ke Controller dan Route

Tidak ada perubahan controller/route pada sub-step ini.

Route yang tetap dipakai:

- `admin.faqs.create`
- `admin.faqs.edit`
- `admin.faqs.destroy`

## Risiko

Rendah. Perubahan hanya view. Data answer tidak dimodifikasi karena limit hanya dilakukan saat rendering.

## Catatan Audit

FAQ Index sekarang lebih mudah dibaca karena answer panjang tidak mendominasi table, tetapi admin tetap bisa masuk ke edit page untuk melihat/mengubah isi lengkap.

