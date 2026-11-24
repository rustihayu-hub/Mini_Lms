# API Contract: API Gateway (Kelompok 5)

## Informasi umum

- Port: 8101
- URL dasar: http://127.0.0.1:8101
- Format data: JSON

Format respons sukses selalu memuat `"success": true`. Respons gagal selalu
memuat `"success": false` beserta objek `error` (berisi `code` dan `message`).
Setiap respons memuat `meta.request_id`.

## Endpoint 1: Pemeriksaan kesehatan Gateway

- Metode dan URL: GET /health
- Autentikasi: tidak
- Request body: -
- Respons sukses: 200

```json
{
  "success": true,
  "data": { "service": "gateway", "status": "healthy" },
  "meta": { "request_id": "req-001" }
}
```

- Respons gagal: 404 `not_found` jika metode atau path salah

## Endpoint 2: Daftar service

- Metode dan URL: GET /api/services
- Autentikasi: tidak
- Request body: -
- Respons sukses: 200

```json
{
  "success": true,
  "data": {
    "course": "http://127.0.0.1:8103",
    "assignment": "http://127.0.0.1:8104",
    "submission": "http://127.0.0.1:8105",
    "notification": "http://127.0.0.1:8106",
    "monitoring": "http://127.0.0.1:8107"
  },
  "meta": { "request_id": "req-001" }
}
```

- Respons gagal: 404 `not_found` jika metode atau path salah

## Endpoint 3: Meneruskan request ke service

- Metode dan URL: ANY /api/{resource}/{path}
- Autentikasi: tidak ditangani oleh Gateway; request diteruskan tanpa validasi token
- Request body: diteruskan apa adanya ke service tujuan, misalnya untuk
  POST /api/courses:

```json
{ "name": "Sistem Terdistribusi" }
```

- Header yang diteruskan: `Authorization`, `Content-Type`, `X-Request-ID`
- Query string diteruskan apa adanya
- Pemetaan resource ke service:

| Resource | Service |
|----------|---------|
| courses | Course |
| assignments | Assignment |
| submissions | Submission File |
| notifications | Notification |
| monitoring | Monitoring |

- Respons sukses: status dan isi dari service tujuan diteruskan apa adanya.
  Contoh GET /api/courses dengan token, status 200:

```json
{
  "success": true,
  "data": [{ "course_id": "course-001", "name": "Sistem Terdistribusi" }],
  "meta": { "request_id": "tes-001" }
}
```

- Respons gagal:

| Status | error.code | Penyebab |
|--------|------------|----------|
| 400 | invalid_json | Body request (Content-Type JSON) pada POST, PUT, atau PATCH bukan JSON yang valid |
| 404 | service_not_found | Nama resource tidak dikenal |
| 502 | bad_gateway | Respons service bukan JSON yang valid |
| 503 | service_unavailable | Service tujuan tidak dapat dihubungi |
| 504 | gateway_timeout | Service tujuan melewati batas timeout (5 detik) |

## Dependensi ke service lain

- Course: tujuan request `/api/courses/...`
- Assignment: tujuan request `/api/assignments/...`
- Submission File: tujuan request `/api/submissions/...`
- Notification: tujuan request `/api/notifications/...`
- Monitoring: tujuan request `/api/monitoring/...`
- `services.json`: berkas konfigurasi alamat semua service, dibaca Gateway
  di setiap request
