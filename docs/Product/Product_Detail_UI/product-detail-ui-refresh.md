# Product Detail UI Refresh

Branch: `feature/product-detail-ui-refresh`

Integrated into: `feature/page-sections-page-management`

## Integration Note

UI product detail ini awalnya dibuat di branch `feature/product-detail-ui-refresh`, lalu dibawa kembali ke branch `feature/page-sections-page-management` agar mapping Page Sections memakai tampilan detail product yang terbaru.

Alasan integrasi:

- Branch Page Sections sebelumnya belum memuat refresh product detail terakhir.
- Akibatnya, saat pindah ke branch Page Sections, frontend product detail terlihat seperti versi lama.
- File detail terbaru dikembalikan ke branch ini bersama CSS dan test terkait, lalu mapping `products.show.*` diverifikasi ulang.

## Scope

Dokumentasi ini mencatat perubahan UI product detail page, booking information form, gallery product detail, dan mapping page/section key untuk kebutuhan Page Sections/admin dashboard ke depan.

Perubahan ini tidak mengubah database, model, controller, ataupun admin product.

## Tujuan

- Merapikan posisi product detail terhadap floating header agar header tidak menutupi konten.
- Membuat header pada product detail tetap readable di atas halaman terang.
- Membuat product gallery detail lebih interaktif dengan main image navigation.
- Menambahkan booking information form yang membentuk pesan WhatsApp dari data yang diisi tamu.
- Menambahkan page key dan section key agar product detail dapat dipetakan oleh admin dashboard di fase Page Sections berikutnya.

## File Terkait

### Frontend View

- `resources/views/frontend/products/show.blade.php`
  - Menambahkan `data-page-key="products.show"`.
  - Menambahkan `id` dan `data-section-key` pada section product detail.
  - Menambahkan Alpine state untuk gallery:
    - `galleryIndex`
    - `galleryImages`
    - `nextGalleryImage()`
    - `previousGalleryImage()`
  - Menambahkan Alpine state untuk booking form:
    - `bookingDate`
    - `adults`
    - `children`
    - `addons`
    - `bookingWhatsappUrl()`
  - Booking WhatsApp URL dibuat dari data form tanpa menyimpan data ke database.

### CSS

- `resources/css/frontend-products.css`
  - Menambahkan product detail header treatment agar header glass terang dan teks gelap.
  - Menambahkan offset khusus `.product-detail-page` supaya konten tidak tertutup header.
  - Merapikan background transition hero ke content.
  - Menambahkan style main gallery navigation.
  - Menambahkan style thumbnail list maksimal 4 gambar.
  - Menambahkan style booking form card, date input, guest stepper, add-ons checkbox, dan sticky sidebar.

### Tests

- `tests/Feature/Frontend/ProductDetailBookingFormTest.php`
  - Memastikan product detail render booking form.
  - Memastikan page key dan section key utama tersedia.
  - Memastikan add-ons dari feature product tampil.
  - Memastikan booking WhatsApp binding tersedia.

- `tests/Feature/Admin/GlobalDefaultMediaAssetsTest.php`
  - Assertion placeholder product disesuaikan karena product detail gallery sekarang mengirim placeholder lewat Alpine JSON data.

## Page Key

Product detail menggunakan page key:

- `products.show`

Page key ini dipasang pada root:

```html
<div class="product-page product-detail-page" data-page-key="products.show">
```

## Section Key Mapping

| Section ID | Section Key | Fungsi |
| --- | --- | --- |
| `products-show-hero` | `products.show.hero` | Wrapper utama bagian atas product detail. |
| `products-show-gallery` | `products.show.gallery` | Gallery image product detail. |
| `products-show-summary` | `products.show.summary` | Badge, title, description, meta, price card, dan chat CTA. |
| `products-show-content` | `products.show.content` | Wrapper area content bawah. |
| `products-show-overview` | `products.show.overview` | Deskripsi/overview product. |
| `products-show-features` | `products.show.features` | Included, excluded, optional, add-ons, important features. Conditional jika product memiliki features. |
| `products-show-itinerary` | `products.show.itinerary` | Itinerary product. Conditional jika product memiliki itineraries. |
| `products-show-notes` | `products.show.notes` | Important notes product. Conditional jika product memiliki notes. |
| `products-show-faq` | `products.show.faq` | FAQ product. Conditional jika product memiliki FAQs. |
| `products-show-booking` | `products.show.booking` | Booking information form dan WhatsApp booking CTA. |

## Gallery Flow

1. Data gallery dibangun dari:
   - `thumbnail_url`
   - relasi `images`
   - fallback Default Media `product`
2. Gambar utama tampil di frame besar.
3. Jika gambar lebih dari satu, tombol previous/next tampil pada frame utama.
4. Thumbnail bawah hanya list preview, maksimal 4 item.
5. Jika jumlah gambar lebih dari 4, thumbnail tetap hanya menampilkan 4; user pindah gambar lewat tombol previous/next di frame utama.
6. Thumbnail bukan slider.

## Booking Information Flow

Booking form tidak menyimpan data ke database. Data form disusun menjadi pesan WhatsApp ketika user klik booking button.

Field yang dikirim ke WhatsApp:

- Product name
- Date
- Adults
- Children
- Duration
- Meeting point
- Add-ons
- Product URL

Generated message dibuat oleh Alpine method:

```js
bookingWhatsappUrl()
```

## Add-ons Source

Add-ons dibaca dari data existing:

- `product_features.label = addon`

Jika product memiliki pickup available, opsi tambahan juga digabung dari:

- `pickup_type` atau fallback `Pickup`
- `Drop off`

Catatan:

- Add-ons tidak memiliki harga pada branch ini.
- Add-ons hanya dipakai sebagai pilihan service yang akan dituangkan ke WhatsApp message.

## Database Terkait

Tidak ada perubahan schema database.

Table yang dibaca:

### `products`

Field relevan:

- `name`
- `slug`
- `thumbnail`
- `short_description`
- `description`
- `meeting_point`
- `duration`
- `whatsapp_number`
- `pickup_available`
- `pickup_type`
- `pickup_note`
- `cta_title`
- `cta_description`
- `cta_button_text`

### `product_images`

Field relevan:

- `product_id`
- `image`
- `sort_order`

Dipakai untuk gallery product detail.

### `product_prices`

Field relevan:

- `product_id`
- `currency`
- `price`

Dipakai untuk menampilkan harga awal IDR dan SGD.

### `product_features`

Field relevan:

- `product_id`
- `label`
- `value`
- `sort_order`

Dipakai untuk feature sections dan add-ons booking form.

### `product_itineraries`

Dipakai untuk section `products.show.itinerary`.

### `product_notes`

Dipakai untuk section `products.show.notes`.

### `product_faqs`

Dipakai untuk section `products.show.faq`.

## Conditional Sections

Beberapa section hanya render jika data tersedia:

- `products.show.features`
- `products.show.itinerary`
- `products.show.notes`
- `products.show.faq`

Hal ini penting untuk admin dashboard:

- Jika section belum muncul, kemungkinan data product untuk section tersebut memang kosong.
- Untuk page builder ke depan, section conditional perlu dibaca sebagai available template, bukan hanya DOM yang sedang tampil.

## Tidak Diubah

- Tidak ada migration baru.
- Tidak ada perubahan schema database.
- Tidak ada perubahan `ProductController`.
- Tidak ada perubahan model `Product`.
- Tidak ada perubahan admin product.
- Tidak ada perubahan global booking CTA settings.
- Saat diintegrasikan ke `feature/page-sections-page-management`, file ini dipakai sebagai versi frontend final untuk `/products/{product}`.
- Mapping Page Sections yang harus tetap ada:
  - `data-page-key="products.show"`
  - `products.show.hero`
  - `products.show.gallery`
  - `products.show.summary`
  - `products.show.content`
  - `products.show.overview`
  - `products.show.features`
  - `products.show.itinerary`
  - `products.show.notes`
  - `products.show.faq`
  - `products.show.booking`

## Validation

Command yang sudah dijalankan:

- `php -l resources\views\frontend\products\show.blade.php`
- `php artisan test tests\Feature\Frontend\ProductDetailBookingFormTest.php`
- `php artisan test tests\Feature\Admin\GlobalDefaultMediaAssetsTest.php --filter test_product_views_use_product_placeholder_when_thumbnail_is_missing`
- `php artisan test`
- `npm.cmd run build`

Hasil:

- PHP lint pass.
- Product detail booking form test pass.
- Default media placeholder test pass.
- Full Laravel test suite pass: 88 tests.
- Vite build pass.

## Catatan Pengembangan Ke Depan

- Jika nanti video product dibutuhkan, lebih scalable membuat media layer khusus seperti `product_media` dengan type `image` atau `video`.
- Jika Page Sections admin mulai mengelola product detail, mapping `products.show.*` ini dapat dipakai sebagai template awal.
- Untuk section conditional, admin dashboard sebaiknya tidak hanya membaca DOM frontend, tetapi juga membaca template registry agar section kosong tetap bisa dikenali.
