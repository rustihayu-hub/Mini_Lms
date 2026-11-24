# Laporan Pengujian API — Monitoring Service (Kelompok 7)

Dokumen ini berisi hasil pengujian fungsional dan penanganan error untuk **Monitoring Service** yang berjalan pada port **`8107`**.

## Informasi Lingkungan Pengujian

- **Nama Proyek:** Monitoring Service Mini-LMS (Kelompok 7)
- **Base URL Lokal:** `http://127.0.0.1:8107`
- **Alat Pengujian:** Postman (`Local` Environment)
- **Penyimpanan Log:** `data/events.json`
- **Tanggal Pengujian:** 16 November 2026

## Daftar Anggota Penguji

| No | Nama Lengkap | NIM |
|---:|---|---|
| 1 | Alif Alfi Takdir | 230209502093 |
| 2 | Muhammad Iyad Gani | 230209502093 |
| 3 | Irfan Jamal | 230209502093 |
| 4 | Maikel Basten | 230209502093 |
| 5 | Arwin | 230209502093 |

---

## Ringkasan Hasil Pengujian

| No | Nama Request | Metode & Endpoint | Skenario Pengujian | Status HTTP | Status |
|---:|---|---|---|---:|---|
| 1 | `01. Cek Health Monitoring Sendiri` | `GET /health` | Cek status layanan mandiri tanpa token | `200 OK` | Lulus |
| 2 | `02. Periksa Status Seluruh Service` | `GET /api/monitoring/services` | Tanpa header Authorization | `200 OK` | Lulus |
| 5 | `03. Catat Event Monitoring` | `POST /api/monitoring/events` | Mengirim payload JSON valid (`service`, `status`, `message`) | `201 Created` | Lulus |
| 6 | `03. Catat Event Monitoring` | `POST /api/monitoring/events` | Mengirim payload JSON kosong `{}` / field tidak lengkap | `400 Bad Request` | Lulus |
| 7 | `03. Catat Event Monitoring` | `GET /api/monitoring/events` | Mengambil seluruh riwayat event yang tersimpan | `200 OK` | Lulus |
| 8 | Uji Rute Tidak Terdaftar | `GET /api/tidak-ada` | Mengakses endpoint yang tidak tersedia | `404 Not Found` | Lulus |

---

## Detail Skenario & Bukti Pengujian

### 1. Cek Health Mandiri (`GET /health`)
- **Tujuan:** Memastikan server Monitoring Service berjalan dan merespons tanpa ketergantungan ke layanan lain.
- **Hasil Aktual:** Server mengembalikan status `200 OK` dengan payload `"status": "UP"`.
- **Bukti Tangkapan Layar:** `docs/bukti/01_health_200.png`

### 2. Pemeriksaan Status Seluruh Layanan (`GET /api/monitoring/services`)
- **Skenario Tanpa Token (`200 OK`):** Monitoring memeriksa status layanan tanpa memanggil Authentication. Jika ada layanan yang mati, `overall_status` ditandai `DEGRADED`.
- **Bukti Tangkapan Layar:** `docs/bukti/02_services_200.png`

### 3. Pencatatan & Verifikasi Event Monitoring (`POST` & `GET /api/monitoring/events`)
- **Skenario Payload Valid (`201 Created`):** Data event berhasil dibuat dengan ID berurutan (`evt-001`, `evt-002`, dst.) dan tersimpan secara permanen di dalam file `data/events.json`.
- **Skenario Payload Kosong (`400 Bad Request`):** Permintaan dengan body `{}` ditolak dengan kode error `invalid_input` (`Field service dan status wajib diisi dalam JSON.`).
- **Skenario Pengambilan Riwayat (`200 OK`):** Endpoint `GET /api/monitoring/events` menampilkan seluruh daftar log peristiwa yang telah tersimpan di `data/events.json`.
- **Bukti Tangkapan Layar:** `docs/bukti/03_events_post_201.png`, `docs/bukti/03_events_post_400.png`, `docs/bukti/03_events_get_200.png`

---

## Checklist Final Sebelum Pengumpulan

- [x] Seluruh endpoint (`/health`, `/api/monitoring/services`, `POST/GET /api/monitoring/events`) berjalan sesuai kontrak.
- [x] Persistensi data terverifikasi pada `data/events.json`.
- [x] `README.md` dan `API_DOCUMENTATION.md` telah diperbarui dan dilengkapi daftar anggota kelompok.
- [ ] File sementara `dummy_auth.php` sudah dihapus dari direktori utama.
- [ ] Koleksi Postman sudah di-export ke dalam folder `postman/`.
- [ ] Screenshot hasil pengujian sudah disimpan di dalam folder `docs/bukti/`.
