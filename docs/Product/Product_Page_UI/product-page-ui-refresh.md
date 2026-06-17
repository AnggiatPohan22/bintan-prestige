# Product Page UI Refresh

Branch: `feature/product-page-ui-refresh`

Integrated into: `feature/page-sections-page-management`

## Integration Note

UI product listing ini awalnya dibuat di branch `feature/product-page-ui-refresh`, lalu dibawa kembali ke branch `feature/page-sections-page-management` karena Page Sections membutuhkan markup frontend terbaru sebagai sumber mapping `page_key` dan `section_key`.

Alasan integrasi:

- Branch Page Sections sempat memakai versi product listing lama karena branch UI belum masuk ke jalur branch ini.
- Jika mapping Page Sections dipasang pada markup lama, admin dashboard akan membaca section yang benar secara key, tetapi frontend yang terlihat oleh user tidak sesuai hasil UI refresh terakhir.
- Solusi yang dipakai adalah memakai ulang file UI terbaru, lalu memastikan `products.index` tetap memiliki `data-page-key`, `id`, dan `data-section-key`.

## Scope

Dokumentasi ini mencatat perubahan tampilan product listing page dan product card di frontend. Perubahan ini fokus pada UI/UX, page key, section key, responsive behavior, dan media viewer tanpa mengubah struktur database atau logic core product yang sudah berjalan.

## Tujuan

- Membuat page `/products` punya hero yang lebih dekat dengan homepage: background image full, title, dan description.
- Menambahkan identitas page/section agar nanti dapat dipetakan ke admin Page Sections.
- Membuat product card lebih visual dan modern dengan image background, overlay, harga dua mata uang, dan title sebagai link detail.
- Menambahkan media viewer di halaman yang sama agar user bisa melihat gallery image tanpa keluar dari product page.
- Menjaga integrasi database existing tetap aman.

## File Terkait

### Frontend View

- `resources/views/frontend/products/index.blade.php`
  - Menambahkan `data-page-key="products.index"`.
  - Menambahkan section key untuk hero, catalog, filter modal, sort modal.
  - Menambahkan product media modal/lightbox untuk image/video action.
  - Menambahkan Alpine state untuk media viewer:
    - `mediaOpen`
    - `mediaType`
    - `mediaTitle`
    - `mediaItems`
    - `mediaIndex`
  - Menambahkan handler:
    - `openProductMedia(event)`
    - `closeProductMedia()`
    - `nextProductMedia()`
    - `previousProductMedia()`

- `resources/views/frontend/products/partials/card.blade.php`
  - Product card memakai thumbnail sebagai background utama.
  - Detail product dibuka melalui title link, bukan button `View`.
  - Title memiliki animated underline cue agar user paham title bisa diklik.
  - Menampilkan:
    - Category
    - Harga IDR sebagai highlight utama
    - Harga SGD sebagai secondary currency
    - Title link
    - Location dan duration
    - Icon action untuk image dan video
  - Icon image mengirim thumbnail dan gallery product ke media modal.
  - Icon video membuka modal empty state karena field video product belum tersedia.

### CSS

- `resources/css/frontend-products.css`
  - Hero product dibuat full background image.
  - Product grid dibuat lebih lega untuk card visual.
  - Product card dibuat sebagai image-overlay card.
  - Icon image/video hidden by default pada desktop dan muncul smooth dari bawah saat hover/focus.
  - Icon action tetap terlihat di mobile karena mobile tidak memiliki hover.
  - Product media modal dibuat dengan frame fixed ratio agar slide image tetap rapi.

- `resources/css/frontend-theme.css`
  - Menambahkan rule agar `.product-page` tidak memakai padding top default dari fixed header.
  - Product hero sekarang bisa berada di bawah floating header seperti homepage.

### Test

- `tests/Feature/Frontend/ProductIndexUiTest.php`
  - Memastikan product index render page key dan section key utama.
  - Memastikan title hero dan product sample muncul.

## Page Key dan Section Key

Page key:

- `products.index`

Section key:

- `products.index.hero`
- `products.index.catalog`
- `products.index.filter_modal`
- `products.index.sort_modal`

Catatan:

- Key ini dibuat sebagai pondasi agar product page bisa dipetakan ke Page Sections/admin page management di step berikutnya.
- Saat ini belum ada table baru untuk page product section template.

## Data yang Dipakai

Product card memakai data existing dari model `Product`:

- `thumbnail`
- `thumbnail_url`
- `name`
- `slug`
- `category`
- `destination`
- `duration`
- `prices`
- `images`

Relasi yang dipakai:

- `category`
- `destination`
- `prices`
- `images`
- `highlights`

Relasi tersebut sudah ada di `Product::scopeFrontendReady()`, sehingga perubahan card tidak menambah query manual baru pada view.

## Schema Database Terkait

Tidak ada perubahan schema database pada branch ini.

Table existing yang dibaca:

### `products`

Field relevan:

- `id`
- `category_id`
- `destination_id`
- `name`
- `slug`
- `thumbnail`
- `short_description`
- `duration`
- `status`

### `product_images`

Field relevan:

- `id`
- `product_id`
- `image`
- `sort_order`

Dipakai untuk media modal image gallery. Thumbnail tetap menjadi image utama di card.

### `product_prices`

Field relevan:

- `product_id`
- `currency`
- `price`

Dipakai untuk:

- IDR sebagai harga utama.
- SGD sebagai harga secondary.

### `categories`

Dipakai untuk label category pada card.

### `destinations`

Dipakai untuk location pada card.

## Media Viewer Flow

1. User hover/focus product card.
2. Icon image dan video muncul dengan animasi dari bawah.
3. User klik icon image.
4. Card dispatch event `open-product-media`.
5. Product page menangkap event dan membuka modal media.
6. Modal menampilkan thumbnail dan gallery product.
7. User bisa slide dengan:
   - tombol previous/next
   - keyboard arrow left/right
8. User bisa tutup modal dengan:
   - tombol `X`
   - klik backdrop
   - keyboard `Escape`

## Video Button

Button video sudah disiapkan secara UI, tetapi masih menampilkan empty state karena belum ada field/relasi video pada product.

Rekomendasi next step untuk video:

- Tambah field `video_url` pada `products`, atau
- Tambah table khusus seperti `product_media` untuk support image/video lebih scalable.

Untuk branch ini, tidak dibuat perubahan database agar tidak mengganggu struktur product existing.

## Validation

Command yang sudah dijalankan:

- `php -l resources\views\frontend\products\index.blade.php`
- `php -l resources\views\frontend\products\partials\card.blade.php`
- `php artisan test tests\Feature\Frontend\ProductIndexUiTest.php`
- `php artisan test`
- `npm.cmd run build`

Hasil:

- PHP lint pass.
- Product index feature test pass.
- Full Laravel test suite pass: 87 tests.
- Vite build pass.

## Catatan Audit

- Tidak ada migration baru.
- Tidak ada perubahan controller product.
- Tidak ada perubahan model product.
- Tidak ada perubahan admin product.
- Saat diintegrasikan ke `feature/page-sections-page-management`, file ini dipakai sebagai versi frontend final untuk `/products`.
- Mapping Page Sections yang harus tetap ada:
  - `data-page-key="products.index"`
  - `products.index.hero`
  - `products.index.catalog`
  - `products.index.filter_modal`
  - `products.index.sort_modal`
- Product detail route tetap memakai `route('products.show', $product)`.
- Link detail dipindahkan dari button `View` ke title product.
- Image card tidak looping. Auto-rotate yang sempat dibuat sudah dihapus.
- Product media modal tetap berada di halaman `/products`, tidak membuka tab baru.
