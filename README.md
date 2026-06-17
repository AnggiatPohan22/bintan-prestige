# Bintan Prestige

Website Tour & Travel berbasis Laravel untuk mengelola dan menampilkan paket wisata, transport, destinasi, dan booking experience Bintan Prestige.

## Branch Develop

Branch `develop` digunakan untuk pengembangan fitur terbaru sebelum masuk ke branch utama. Semua update berikutnya diarahkan ke `origin/develop` agar tidak mengganggu core yang sudah stabil di `main`.

## Update Terbaru di Develop

### Backend

- Admin dashboard untuk pengelolaan produk, kategori, destinasi, dan konten pendukung.
- CRUD produk dengan relasi kategori, destinasi, harga, gambar, thumbnail, highlight, fitur, itinerary, note, FAQ, dan CTA booking.
- Toggle status publish dan featured product.
- Soft delete, restore, dan force delete untuk kategori dan destinasi.
- Struktur service layer untuk pengelolaan produk dan konten turunannya.
- Route admin terpisah di `routes/admin.php`.
- Route frontend terpisah di `routes/frontend.php`.

### Frontend

- Homepage luxury travel dengan identitas visual Black & Gold.
- Header frontend reusable dengan mode transparan, efek blur soft gold saat scroll, button `Plan Trip`, dan icon arrow modern.
- Footer frontend reusable untuk semua halaman public.
- Homepage section:
  - Hero fullscreen dengan background dinamis yang siap dihubungkan ke upload backend.
  - Floating booking/search form.
  - Featured products.
  - Categories.
  - Destinations.
  - Why Choose Us.
  - FAQ preview.
  - WhatsApp CTA.
- Product listing responsive dengan 4 cards desktop, swipe horizontal di mobile, pagination, breadcrumb, filter modal, dan sort modal.
- Product detail page dengan gallery, price/CTA, highlights, overview, features, itinerary, notes, FAQ, dan sticky booking card.
- CSS frontend dipisah dari admin melalui `resources/css/frontend.css` agar halaman public tidak ikut load style admin.
- JavaScript frontend dipisah di `resources/js/frontend.js` untuk behavior header dan pengembangan interaksi frontend berikutnya.

## Tech Stack

- Laravel 13
- Blade
- Tailwind CSS
- Alpine.js
- Vite
- MySQL

## Development

Install dependency:

```bash
composer install
npm install
```

Build asset:

```bash
npm run build
```

Run development asset server:

```bash
npm run dev
```

Run Laravel server:

```bash
php artisan serve
```

## Branch Workflow

1. Kerjakan update di branch `develop`.
2. Commit perubahan dengan pesan yang jelas.
3. Push ke `origin develop`.
4. Merge ke `main` hanya jika fitur sudah stabil dan siap dipakai.
