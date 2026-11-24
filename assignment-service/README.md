# Assignment Service

Assignment Service merupakan layanan dalam sistem Mini-LMS yang bertanggung jawab untuk mengelola data dan operasi assignment (tugas).

## Struktur

- `index.php` — endpoint utama API.
- `data/assignments.json` — penyimpanan data assignment.
- `API_CONTRACT.md` — dokumentasi kontrak API.
- `services.json` — konfigurasi service.
- `tests/` — script pengujian API.
- `docs/` — dokumentasi dan bukti pengujian.
- `postman/` — koleksi dan environment Postman.

## Menjalankan Service

Assignment Service menggunakan PHP. Jalankan server PHP dari folder service:

```powershell
php -S 127.0.0.1:8104 -t .
```

## API

Detail endpoint, request, response, dan pengujian dapat dilihat pada `API_CONTRACT.md`, `docs/TESTING.md`, dan koleksi Postman di folder `postman/`.

## Integrasi

Service ini merupakan bagian dari Mini-LMS dan direncanakan terintegrasi melalui Gateway.
