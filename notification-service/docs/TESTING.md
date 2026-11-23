# Pengujian Notification Service (Kelompok 2)

## Cara menguji

1. Jalankan service: `php -S 127.0.0.1:8106 index.php` (Terminal Laragon, folder proyek).
2. **Uji mandiri** (Authentication belum jalan): jalankan dengan `$env:AUTH_MODE="format_only"; php -S 127.0.0.1:8106 index.php`.
3. **Uji terintegrasi**: jalankan dengan pengaturan bawaan (`AUTH_MODE=remote`), login lewat Gateway, lalu isi variabel `token` pada environment Postman dengan token asli.
4. Postman: impor `postman/Mini-LMS.postman_collection.json` dan `postman/Lokal.postman_environment.json`, pilih environment **Lokal**, klik **Run** pada koleksi, lalu screenshot hasilnya ke `docs/bukti/`.
5. Alternatif skrip: `.\tests\test_api.ps1 -Token "<token>"` atau `./tests/test_api.sh http://127.0.0.1:8106 <token>`.

## Skenario uji

| No | Skenario | Request | Hasil diharapkan | Hasil aktual |
|---|---|---|---|---|
| 1 | Health check | GET /health | 200, `status: healthy` | 200 |
| 2 | Buat notifikasi (sukses) | POST /api/notifications, body valid | 201, `is_read: false` | 201 |
| 3 | Buat notifikasi tanpa `type` | POST, tanpa field `type` | 201, `type: general` | 201 |
| 4 | Field wajib kosong | POST, body `{}` | 400 `validation_error` | 400 |
| 5 | JSON rusak | POST, body `{rusak` | 400 `invalid_json` | 400 |
| 6 | Field bertipe salah | POST, `message` berupa angka | 400 `validation_error` | 400 |
| 7 | Content-Type salah | POST, `text/plain` | 415 `unsupported_media_type` | 415 |
| 8 | Tanpa token | POST/GET/PATCH tanpa Authorization | 401 `unauthorized` | 401 |
| 9 | Token ditolak Authentication | Bearer token palsu (mode remote) | 401 `unauthorized` | 401 |
| 10 | Ambil notifikasi user | GET ?user_id=user-001 | 200, array milik user itu saja | 200 |
| 11 | Ambil tanpa `user_id` | GET /api/notifications | 400 `validation_error` | 400 |
| 12 | Tandai dibaca | PATCH /api/notifications/{id}/read | 200, `is_read: true` | 200 |
| 13 | Bukti perubahan | GET setelah PATCH | notifikasi tadi `is_read: true` | benar |
| 14 | Id tidak ada | PATCH /api/notifications/notif-999/read | 404 `notification_not_found` | 404 |
| 15 | Endpoint tidak dikenal | GET /api/xyz | 404 `not_found` | 404 |
| 16 | Authentication membalas rusak | Auth membalas bukan JSON | 502 `auth_bad_response` | 502 |
| 17 | Authentication lambat (> 3 dtk) | Auth tidak menjawab tepat waktu | 504 `auth_timeout` | 504 |
| 18 | Authentication mati | Matikan Authentication, panggil GET dengan token | 503 `auth_unavailable`, tidak menggantung | 503 (< 1 dtk) |
| 19 | Health saat Auth mati | GET /health | tetap 200 | 200 |
| 20 | Mode uji mandiri | `AUTH_MODE=format_only`, token apa saja | 200; tanpa header tetap 401 | sesuai |

## Catatan hasil

Hasil aktual di atas diperoleh dari pengujian otomatis (Postman/Newman: 13 request dan 39 assertion lulus semua; skrip `tests/test_api.sh`: 12 dari 12 lulus; skenario 1 sampai 20 diuji terhadap service nyata) dengan **Authentication Service tiruan** pada port 8102.

**Yang masih harus dilakukan kelompok:**
- [ ] Jalankan ulang Collection Runner terhadap Authentication **asli** milik kelompok Authentication dan simpan screenshot ke `docs/bukti/` (mis. `runner-hasil.png`).
- [ ] Jalankan ulang lewat **Gateway** (`base_url` = `http://127.0.0.1:8101`) dan simpan screenshotnya.
- [ ] Screenshot skenario 18 (Authentication dimatikan, respons 503) dan skenario setelah Authentication dihidupkan kembali.
- [ ] Ubah kolom "Hasil aktual" jika ada perbedaan pada lingkungan kalian.

## Uji integrasi dan kegagalan (isi saat integrasi)

| Skenario | Bukti yang disimpan | Status |
|---|---|---|
| Submission membuat notifikasi lewat Gateway | `docs/bukti/integrasi-submission.png` | belum |
| Monitoring menampilkan notification `healthy` | `docs/bukti/monitoring-healthy.png` | belum |
| Notification dimatikan: Gateway menjawab 503/504, Monitoring `unhealthy` | `docs/bukti/gagal-notification.png` | belum |
| Notification dihidupkan lagi: request berhasil, Monitoring `healthy` | `docs/bukti/recovery.png` | belum |
