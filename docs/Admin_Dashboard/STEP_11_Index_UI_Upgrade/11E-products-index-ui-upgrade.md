# STEP 11E - Products Index UI Upgrade

## Tujuan

Merapikan halaman `Products Index` agar lebih clean, modern, informatif, dan responsive. Halaman ini juga mendapat filter UI untuk memudahkan admin mencari product berdasarkan category, destination, dan status.

## File yang Berubah

- `resources/views/backend/products/index.blade.php`
- `app/Http/Controllers/Admin/ProductController.php`

## File yang Terkait

- `resources/css/admin.css`
- `routes/admin.php`
- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Destination.php`
- Product child data:
  - highlights
  - features
  - faqs
  - itineraries
  - notes
  - images

## Perubahan UI

- Halaman memakai `admin-page`.
- Header memakai `admin-page-header`, `admin-page-title`, dan `admin-page-subtitle`.
- Tombol `Create Product` tetap di header memakai `admin-btn-primary`.
- Product List dibungkus dengan `admin-card`.
- Table lama diganti menjadi horizontal product card list.
- Product thumbnail memakai fixed ratio agar rapi.
- Jika thumbnail kosong, tampil placeholder `No Image`.
- Product title, category, destination, dan gallery count tetap ditampilkan.
- Current Rate card dibuat compact dan lebar cukup agar IDR tidak turun baris.
- IDR tetap menjadi harga utama.
- SGD ditampilkan lebih kecil di bawah IDR.
- Content indicator memakai icon bulat:
  - feature icon
  - itinerary icon
  - FAQ icon
  - core feature warning/check icon
- Icon dibuat satu baris dengan spacing agar tidak mepet.
- Status product memakai pill badge dengan dot:
  - Published: green pill
  - Draft: amber pill
- Edit dan delete memakai icon button bulat.
- Empty state dibuat modern dengan pesan `No products found` dan tombol reset.

## Filter UI

Filter berada di atas Product List.

Field:

- Search by product name.
- Category filter.
- Destination filter.
- Status filter.

Query parameter:

- `search`
- `category_id`
- `destination_id`
- `status`

Filter memakai method GET sehingga URL bisa diaudit dan pagination bisa mempertahankan filter.

Reset filter memakai link ke `admin.products.index`.

## Status Filter Normalization

Ada audit final pada status filter karena raw database bisa memiliki variasi status lama seperti `1` dan status canonical seperti `published`. Jika semua raw value langsung ditampilkan, dropdown bisa memiliki dua option yang sama-sama terlihat sebagai `Published`.

Solusi final dilakukan di Blade UI:

- Raw statuses dikumpulkan dari `$statuses`.
- Value `published` diprioritaskan sebagai Published.
- Value `1` hanya fallback jika `published` tidak ada.
- Value `draft` diprioritaskan sebagai Draft.
- Value selain `published` dan `1` dipakai fallback Draft jika `draft` tidak ada.

Dampak:

- Dropdown hanya menampilkan satu `Published` dan satu `Draft`.
- Tidak ada update data database.
- Tidak ada perubahan query toggle status.
- Tidak ada perubahan schema.

## Perubahan Controller

File:

- `app/Http/Controllers/Admin/ProductController.php`

Perubahan pada `index(Request $request)`:

- Product query menambahkan eager loading:
  - `category`
  - `destination`
  - `prices`
- Product query menambahkan count:
  - `highlights`
  - `features`
  - `faqs`
  - `itineraries`
  - `notes`
  - `images`
  - `included_features_count`
  - `excluded_features_count`
- Search tetap memakai filter nama product.
- Filter baru:
  - `when($request->category_id)`
  - `when($request->destination_id)`
  - `when($request->status)`
- Pagination tetap `paginate(10)->withQueryString()`.
- View sekarang menerima:
  - `$products`
  - `$categories`
  - `$destinations`
  - `$statuses`

## Route yang Dipakai

File:

- `routes/admin.php`

Route existing tetap dipakai:

- `admin.products.index`
- `admin.products.create`
- `admin.products.edit`
- `admin.products.destroy`
- `admin.products.toggle-status`

Route product lain tetap tersedia dan tidak diubah:

- `admin.products.toggle-featured`
- `admin.products.search-booking.update`
- `admin.products.images.thumbnail`
- `admin.products.images.destroy`
- `admin.products.thumbnail.destroy`
- `admin.products.highlights.*`
- `admin.products.features.*`
- `admin.products.faqs.*`
- `admin.products.itineraries.*`
- `admin.products.notes.*`

## Dampak ke Database

Tidak ada perubahan database.

- Tidak ada migration.
- Tidak ada schema update.
- Tidak ada table baru.
- Tidak ada column baru.
- Tidak ada perubahan data product.

Filter status hanya membaca data existing. Normalisasi status di dropdown hanya visual.

## Dampak ke Model

Tidak ada perubahan model.

- Tidak ada relationship baru.
- Tidak ada scope baru.
- Tidak ada accessor/mutator baru.
- Product relationship existing hanya dibaca oleh query `with()` dan `withCount()`.

## Dampak ke CRUD dan Child Data

Tidak ada perubahan store/update/delete logic.

- Product create/edit form tidak disentuh.
- Product gallery logic tidak disentuh.
- Product FAQ/notes/itinerary/highlight/feature logic tidak disentuh.
- Image upload logic tidak disentuh.
- Delete action tetap mengirim `DELETE` ke route existing.
- Status toggle tetap memanggil route existing.

## Risiko

Rendah-sedang.

Alasannya:

- Ada perubahan controller index untuk filter dan query count, tetapi hanya read/query untuk halaman list.
- Tidak ada write operation baru.
- Tidak ada perubahan schema/model.
- Risiko utama adalah jika status legacy di database beragam. UI sudah mengurangi ambiguitas dengan normalisasi label filter dan product card label.

## Catatan Audit

Products Index sekarang lebih cocok untuk admin operasional karena informasi utama terlihat dalam satu card horizontal: product, price, content readiness, status, dan action. Filter membantu admin menemukan product tanpa mengubah struktur database.

