# Monitoring Service Mini-LMS (Kelompok 7)

Monitoring Service memeriksa ketersediaan layanan Mini-LMS secara langsung tanpa membaca registry Gateway serta mencatat log peristiwa (*event monitoring*) secara permanen. Pemisahan ini mencegah ketergantungan melingkar: Gateway boleh meneruskan permintaan monitoring, tetapi Monitoring Service tidak bergantung kembali kepada Gateway untuk mengetahui alamat layanan.

## Anggota Kelompok 7

| No | Nama Lengkap | NIM |
|---:|---|---|
| 1 | Alif Alfi Takdir | 230209502093 |
| 2 | Ahmad Iyad Gani | 2302095502010 |
| 3 | Irfan Jamal | 230209502023 |
| 4 | Maikel Basten | 230209502093 |
| 5 | Arwin | 230209502093 |

## Daftar Endpoint Utama

| Metode | Endpoint | Akses | Kegunaan |
|---|---|---|---|
| `GET` | `/health` | Publik | Memeriksa status hidup proses Monitoring Service secara mandiri. |
| `GET` | `/api/monitoring/services` *(alias: `/api/health/all`)* | Terlindungi (`Bearer Token`) | Memvalidasi token ke Auth Service lalu memeriksa status kesehatan seluruh layanan terdaftar. |
| `POST` | `/api/monitoring/events` | Publik / Internal | Mencatat log peristiwa (*event*) hasil pemeriksaan atau status layanan ke `data/events.json`. |
| `GET` | `/api/monitoring/events` | Publik / Internal | Mengambil seluruh riwayat log peristiwa yang tersimpan di `data/events.json`. |

## Struktur Direktori

```text
Kelompok 7 - Monitoring Service/
├── api/
│   ├── events.php           # Handler pencatatan & pengambilan log event (/api/monitoring/events)
│   ├── health.php           # Handler cek health mandiri (/health)
│   └── health_all.php       # Handler cek status seluruh layanan (/api/monitoring/services)
├── data/
│   └── events.json          # Penyimpanan permanen log peristiwa (terbuat otomatis saat POST)
├── docs/
│   ├── TESTING.md           # Laporan & rangkuman hasil pengujian Postman
│   └── bukti/               # Screenshot bukti pengujian endpoint (200, 201, 400, 401, 503)
├── postman/                 # File export koleksi Postman Kelompok 7
├── API_DOCUMENTATION.md     # Dokumentasi lengkap spesifikasi kontrak & contoh respons API
├── health_functions.php     # Fungsi helper validasi token, pengecekan HTTP, dan format respons
├── index.html               # Halaman antarmuka status Monitoring Service
├── README.md                # Dokumentasi utama proyek
├── router.php               # Pengatur rute utama (front controller)
└── start.bat                # Skrip menjalankan server PHP bawaan pada port 8107

## Menjalankan di LAN

Gunakan PHP 7.4 atau yang lebih baru. Contoh berikut membuka layanan pada seluruh antarmuka jaringan di port `8107`:

```bash
AUTH_SERVICE_URL=http://HOST_AUTH:8102 \
COURSE_SERVICE_URL=http://HOST_COURSE:8103 \
ASSIGNMENT_SERVICE_URL=http://HOST_ASSIGNMENT:8104 \
SUBMISSION_SERVICE_URL=http://HOST_SUBMISSION:8105 \
NOTIFICATION_SERVICE_URL=http://HOST_NOTIFICATION:8106 \
php -S 0.0.0.0:8107 router.php
```

Ganti setiap nama host dengan alamat LAN atau DNS yang sesuai. Kode tidak menyimpan alamat IP tertentu.

### Variabel lingkungan

| Variabel | Wajib | Kegunaan |
|---|---:|---|
| `AUTH_SERVICE_URL` | Ya | URL dasar Auth Service, termasuk untuk validasi token. |
| `COURSE_SERVICE_URL` | Ya | URL dasar Course Service. |
| `ASSIGNMENT_SERVICE_URL` | Ya | URL dasar Assignment Service. |
| `SUBMISSION_SERVICE_URL` | Ya | URL dasar Submission Service. |
| `NOTIFICATION_SERVICE_URL` | Ya | URL dasar Notification Service. |
| `MONITORING_TIMEOUT_SECONDS` | Tidak | Batas waktu tiap permintaan, bawaan 3 detik; rentang 0,1–30 detik. |

Jalur health bawaan berturut-turut adalah `/health`, `/api/health`, `/health`, `/health`, dan `/health`. Bila implementasi layanan berbeda, atur `AUTH_HEALTH_PATH`, `COURSE_HEALTH_PATH`, `ASSIGNMENT_HEALTH_PATH`, `SUBMISSION_HEALTH_PATH`, atau `NOTIFICATION_HEALTH_PATH`.

## Integrasi Gateway

Gateway menerima rute privat `/api/v1/monitoring/...`, menghapus prefiks tersebut, lalu meneruskannya ke Monitoring Service. Contoh pemetaan:

- `GET /api/v1/monitoring/health` → `GET http://HOST_MONITORING:8107/health`
- `GET /api/v1/monitoring/all` → `GET http://HOST_MONITORING:8107/api/health/all`

Header `Authorization` harus dipertahankan saat Gateway meneruskan endpoint terlindungi.

## Pengujian singkat

```bash
curl -i http://HOST_MONITORING:8107/health
curl -i -H 'Authorization: Bearer TOKEN' http://HOST_MONITORING:8107/api/health/all
```

Endpoint agregat tetap merespons `200` setelah autentikasi berhasil meskipun satu layanan `DOWN`; hasil tersebut ditandai dengan `overall_status: DEGRADED`. Kegagalan autentikasi menghasilkan `401`, sedangkan Auth Service yang tidak dapat dihubungi menghasilkan `503`.

---

## Pengujian dengan Postman (Drag & Drop)

Di dalam folder ini telah disediakan file **`postman_collection.json`** yang sudah dikhususkan untuk service ini:
1. Buka aplikasi **Postman**.
2. **Drag & drop** file `postman_collection.json` dari folder ini langsung ke jendela Postman (atau klik tombol **Import** lalu pilih file ini).
3. Seluruh endpoint pengujian milik kelompok ini akan otomatis terdaftar dan siap dijalankan tanpa perlu mengetik URL atau body secara manual.
4. Variabel `base_url` sudah otomatis diset ke port kelompok ini. Jika dijalankan antar-laptop di jaringan LAN, cukup sesuaikan nilai `base_url` dengan IP laptop tujuan.
