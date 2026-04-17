# Cara Mengatur Varian per Menu

## Alur Pengaturan Varian (Seperti GoFood)

### 1️⃣ Buat Grup Varian
Masuk ke **Manajemen Grup Varian** dan buat grup-grup seperti:
- **Cup Size** (Hot, Ice, Warm)
- **Sugar Level** (0%, 25%, 50%, 75%)
- **Add-ons** (Boba, Pudding, Jelly)

### 2️⃣ Petakan Varian ke Menu
- Buka **Manajemen Menu**
- Klik icon **⚙️ Kelola Varian** di setiap menu
- Pilih grup varian mana saja yang relevan untuk menu itu
- Klik **Simpan Perubahan**

### 3️⃣ Hasil
Ketika pelanggan membeli menu:
- **Menu Americano** → Tampil: Cup Size, Sugar Level
- **Menu Milkshake** → Tampil: Add-ons, Sugar Level  
- **Menu Nasi Goreng** → Tidak ada varian (karena tidak di-centang)

## Struktur Database
```
variant_groups (Grup Varian)
├── id
├── nama_group (Cup Size, Add-ons, dll)
├── selection_type (single/multiple)
└── is_required (wajib dipilih?)

variant_options (Opsi dalam Grup)
├── id
├── variant_group_id
├── nama_opsi (Hot, Boba, dll)
└── extra_price (harga tambahan)

menu_variant_group (Pivot - Pemetaan Menu ke Grup)
├── menu_id
└── variant_group_id
```

## Keuntungan Sistem Ini
✅ Setiap menu bisa punya varian yang berbeda
✅ Varian bisa dipakai ulang di berbagai menu
✅ Mudah menambah/mengubah harga tambahan
✅ Fleksibel seperti aplikasi food order komersial
