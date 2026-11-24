# Notification Service: Mini LMS Terdistribusi

Service **Notification** untuk proyek Mini LMS Terdistribusi (Mata Kuliah Sistem Terdistribusi, PTIK A).

| | |
|---|---|
| Kelompok | 2 |
| Service | Notification |
| Port | 8106 |
| Bahasa | PHP (dijalankan dengan Laragon / server bawaan PHP) |
| Penyimpanan | Berkas JSON (`data/notifications.json`) |

## Anggota dan kontribusi

| Nama | NIM | Kontribusi |
|---|---|---|
| (isi) | (isi) | (contoh: kode endpoint POST dan GET) |
| (isi) | (isi) | (contoh: PATCH, validasi token, uji kegagalan) |
| (isi) | (isi) | (contoh: kontrak API, koleksi Postman, integrasi) |
| (isi) | (isi) | (contoh: dokumentasi, diagram, bukti pengujian) |

## Endpoint

| Metode | URL | Fungsi | Token |
|---|---|---|---|
| GET | `/health` | Cek service sehat | Tidak |
| GET | `/api/notifications?user_id=...` | Daftar notifikasi satu user | Ya |
| POST | `/api/notifications` | Buat notifikasi | Ya |
| PATCH | `/api/notifications/{id}/read` | Tandai sudah dibaca | Ya |

Detail request, respons, dan kode error ada di [API_CONTRACT.md](API_CONTRACT.md).

## Prasyarat
- [Laragon](https://laragon.org/download) (Full) atau PHP 8.x dengan ekstensi **curl** aktif
- [Postman](https://www.postman.com/downloads) untuk pengujian

## Menjalankan

1. Buka **Terminal Laragon**, lalu masuk ke folder proyek:
   ```
   cd C:\laragon\www\kelompok-2-notification
   ```
2. Jalankan service (jangan tutup terminalnya; hentikan dengan Ctrl + C):
   ```
   php -S 127.0.0.1:8106 index.php
   ```
3. Cek di browser: http://127.0.0.1:8106/health

Agar bisa diakses laptop lain di Wi-Fi yang sama, gunakan `0.0.0.0` dan izinkan port di firewall:
```
php -S 0.0.0.0:8106 index.php
```
Lalu minta Gateway dan Monitoring mengganti alamat notification di `services.json` dengan IP laptop ini (cek dengan `ipconfig`).

### Pengaturan (opsional)

Alamat semua service dibaca dari [`services.json`](services.json); jangan menulis alamat di kode.

| Variabel | Default | Fungsi |
|---|---|---|
| `HTTP_TIMEOUT` | `3` | Batas waktu (detik) memanggil service lain |

Jalankan Notification Service (PowerShell; jalankan dari root proyek):
```
php -S 127.0.0.1:8106 .\notification-service\index.php
```

## Pengujian

- **Postman**: impor koleksi dan environment, pilih environment **Lokal**, lalu jalankan dengan Collection Runner. Token tidak diperlukan.
- **Skrip**: `tests/test_api.ps1` (PowerShell) atau `tests/test_api.sh` (Bash). Contoh: `.\tests\test_api.ps1 -Token "<token>"`
- Hasil dan bukti: [docs/TESTING.md](docs/TESTING.md) dan folder `docs/bukti/`.

## Reset data demo

Kembalikan data awal sebelum demo:
```
copy data\notifications.seed.json data\notifications.json
```

## Struktur folder

```
kelompok-2-notification/
├── README.md
├── API_CONTRACT.md
├── .env.example
├── services.json
├── config.php
├── index.php
├── data/
│   ├── notifications.json
│   └── notifications.seed.json
├── tests/
│   ├── test_api.ps1
│   └── test_api.sh
├── postman/
│   ├── Mini-LMS.postman_collection.json
│   └── Lokal.postman_environment.json
└── docs/
    ├── DIAGRAM.md
    ├── TESTING.md
    └── bukti/
```

## Diagram

Lihat [docs/DIAGRAM.md](docs/DIAGRAM.md) (hubungan service dan alur request).

## Keterbatasan yang diketahui

- Data disimpan di satu berkas JSON, cukup untuk demo tetapi tidak untuk beban besar. Tulis-baca dikunci (`flock`) agar aman dari tabrakan sederhana.
- Server bawaan PHP (`php -S`) melayani satu request pada satu waktu, jadi tidak cocok untuk produksi.
- Endpoint notifikasi publik; klien dapat membaca notifikasi untuk `user_id` mana pun yang diketahui.
- Belum ada endpoint hapus dan belum ada paginasi pada daftar notifikasi.

