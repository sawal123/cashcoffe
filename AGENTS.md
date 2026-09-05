# AGENTS.md

## Peran

Jadilah asisten programmer Laravel + Livewire + Alpine.js + Vue.js + TailwindCSS yang handal, teliti, efisien, dan fokus menyelesaikan tugas tanpa memperluas scope.

Prioritas utama:

1. Perbaiki hanya masalah yang diminta.
2. Pertahankan behavior existing yang tidak berkaitan dengan task.
3. Hindari regresi.
4. Jangan melakukan refactor besar tanpa diminta.
5. Jangan mengubah business logic jika task hanya berkaitan dengan UI/UX.

---

## Stack Project

Project ini menggunakan sebagian atau seluruh teknologi berikut:

- Laravel
- Livewire
- Alpine.js
- Vue.js
- TailwindCSS
- MySQL
- Vite
- PHPUnit / Laravel Feature Test

Ikuti pola dan struktur existing project sebelum membuat pola baru.

---

## Scope Kerja

Kerjakan hanya:

- file yang disebutkan pada prompt;
- dependency langsung yang benar-benar diperlukan untuk menyelesaikan task.

DILARANG:

- audit seluruh project;
- refactor area lain;
- cleanup kode yang tidak berkaitan;
- rename file/class/function tanpa kebutuhan;
- mengubah arsitektur;
- mengubah UI halaman lain;
- mengubah business logic di luar task.

Jika task dapat diselesaikan di layer Blade/CSS/JS, jangan mengubah PHP/business logic.

Jika task dapat diselesaikan di satu atau dua file, jangan memperluas perubahan ke banyak file.

---

## Membaca File

Gunakan konteks yang sudah tersedia.

Aturan:

- Jangan membaca ulang file jika konteks masih cukup.
- Jangan gunakan `read_file` pada file yang sama lebih dari 2 kali.
- Jangan membaca file besar secara penuh jika hanya beberapa bagian yang dibutuhkan.
- Gunakan search/find untuk mencari method, component, class, atau selector terlebih dahulu.

Jika membutuhkan file di luar daftar utama:

- boleh dibaca maksimal 1 kali;
- hanya jika masih berhubungan langsung dengan task.

---

## Saat Menemukan Bug

Cari penyebab paling dekat dengan perubahan/task terlebih dahulu.

Jangan langsung memperbaiki komponen global.

Urutan investigasi:

1. file yang sedang diubah;
2. dependency langsung;
3. component/helper terkait;
4. business logic hanya jika benar-benar terbukti bermasalah.

Jika terdapat regresi setelah sebuah PR/perubahan:

- bandingkan dengan branch/base sebelumnya;
- pertahankan behavior yang sebelumnya sudah benar;
- jangan redesign bagian yang tidak diminta.

---

## UI / UX

Untuk task responsive:

- Jangan merusak desktop jika fokus task adalah mobile/tablet.
- Pertahankan desktop existing jika sebelumnya sudah benar.
- Jangan menggunakan `overflow-x-hidden` global untuk menyembunyikan masalah layout.
- Perbaiki sumber overflow.
- Gunakan breakpoint secara konsisten.
- Pastikan flex/grid menggunakan `min-w-0` jika dibutuhkan.
- Horizontal scroll hanya boleh digunakan pada container yang memang dirancang scroll seperti tabs/chips.

Viewport umum yang perlu diperhatikan bila relevan:

- 375px
- 390px
- 422px
- 640px
- 768px
- 1024px
- 1280px
- 1440px+

Untuk aplikasi POS:

- utamakan touch target yang jelas;
- hindari kontrol terlalu kecil;
- informasi penting seperti total/cart harus mudah diakses;
- jangan mengubah workflow transaksi tanpa diminta.

---

## Livewire

Jangan mengubah method Livewire yang sudah bekerja hanya karena UI bermasalah.

Jika `wire:click`, `wire:model`, event, atau modal tidak bekerja:
periksa terlebih dahulu:

- overlay;
- z-index;
- pointer-events;
- `x-show`;
- Alpine state;
- struktur DOM;
- element fixed/absolute;
- modal backdrop;
- breakpoint CSS.

Pastikan apakah event Livewire benar-benar tidak terpanggil sebelum mengubah method backend.

Jangan mengubah:

- pricing;
- cart calculation;
- discount;
- member;
- payment;
- inventory;
- stock;
- submit order;

kecuali secara eksplisit diminta.

---

## Database

Jangan menjalankan operasi destruktif.

DILARANG:

```bash
php artisan migrate:fresh
php artisan migrate:reset
php artisan db:wipe
```
