# API Contract: Notification Service (Kelompok 2)

## Informasi umum
- Port: 8106
- URL dasar: http://127.0.0.1:8106 (lewat Gateway: http://127.0.0.1:8101/api/notifications)
- Format data: JSON (`Content-Type: application/json`)
- Header autentikasi tidak diperlukan. `X-Request-ID: <id unik>` opsional; jika kosong dibuat otomatis.

Format respons mengikuti kontrak bersama (modul bagian 8).

Sukses:
```json
{ "success": true, "data": { }, "meta": { "request_id": "req-001" } }
```
Gagal (status HTTP selalu mencerminkan kegagalan, tidak pernah 200):
```json
{ "success": false, "error": { "code": "kode_error", "message": "Penjelasan" }, "meta": { "request_id": "req-001" } }
```

Objek notifikasi:
| Field | Tipe | Keterangan |
|---|---|---|
| `id` | string | Dibuat server, contoh `notif-001` |
| `user_id` | string | Penerima notifikasi |
| `message` | string | Isi notifikasi (maks. 500 karakter) |
| `type` | string | Jenis, contoh `submission`. Default `general` |
| `submission_id` | string atau null | Opsional, mengacu ke submission pemicu |
| `is_read` | boolean | `false` saat dibuat |
| `created_at` | string | Waktu pembuatan (ISO 8601) |

## Endpoint 1: Buat notifikasi
- Metode dan URL: `POST /api/notifications`
- Autentikasi: tidak diperlukan
- Request body (`user_id` dan `message` wajib; `type` dan `submission_id` opsional):
  ```json
  { "user_id": "user-001", "message": "Submission berhasil diterima", "type": "submission", "submission_id": "sub-001" }
  ```
- Respons sukses: `201 Created`
  ```json
  {
    "success": true,
    "data": {
      "id": "notif-002",
      "user_id": "user-001",
      "message": "Submission berhasil diterima",
      "type": "submission",
      "submission_id": "sub-001",
      "is_read": false,
      "created_at": "2026-11-13T08:30:00+08:00"
    },
    "meta": { "request_id": "req-001" }
  }
  ```
- Respons gagal:
  | Status | `error.code` | Penyebab |
  |---|---|---|
  | 400 | `invalid_json` | Isi request bukan JSON yang valid |
  | 400 | `validation_error` | `user_id` atau `message` kosong/bukan teks, `type` bukan teks, atau `message` lebih dari 500 karakter |
  | 415 | `unsupported_media_type` | `Content-Type` bukan `application/json` |

## Endpoint 2: Lihat notifikasi milik satu user
- Metode dan URL: `GET /api/notifications?user_id=user-001`
- Autentikasi: tidak diperlukan
- Request body: -
- Respons sukses: `200 OK`, `data` berupa array objek notifikasi (array kosong jika belum ada)
- Respons gagal:
  | Status | `error.code` | Penyebab |
  |---|---|---|
  | 400 | `validation_error` | Parameter `user_id` tidak diisi |

## Endpoint 3: Tandai sudah dibaca
- Metode dan URL: `PATCH /api/notifications/{id}/read` (contoh `/api/notifications/notif-001/read`)
- Autentikasi: tidak diperlukan
- Request body: -
- Respons sukses: `200 OK`, `data` berisi objek notifikasi dengan `is_read: true`
- Respons gagal:
  | Status | `error.code` | Penyebab |
  |---|---|---|
  | 404 | `notification_not_found` | `id` tidak ada |

## Endpoint 4: Health check
- Metode dan URL: `GET /health`
- Autentikasi: tidak diperlukan
- Respons sukses: `200 OK`
  ```json
  { "success": true, "data": { "service": "notification", "status": "healthy" }, "meta": { "request_id": "req-001" } }
  ```

## Kode error umum
- `404 not_found`: endpoint atau method tidak dikenal
- `500 storage_error` / `500 config_error`: berkas data atau konfigurasi service bermasalah

## Dependensi ke service lain
- Notification tidak bergantung pada Authentication. Submission dapat mengirim notifikasi internal lewat `POST /api/notifications`.

## Dipanggil oleh
- **Submission File (port 8105)**: `POST /api/notifications` setelah pengumpulan berhasil.
- **Gateway (port 8101)**: meneruskan `/api/notifications/...`.
- **Monitoring (port 8107)**: `GET /health`.
