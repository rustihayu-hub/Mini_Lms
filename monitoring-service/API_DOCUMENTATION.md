# Dokumentasi API Monitoring Service

Base URL langsung pada LAN: `http://HOST_MONITORING:8107`.

## `GET /health`

Endpoint publik untuk memastikan proses Monitoring Service hidup. Endpoint ini tidak menghubungi layanan lain dan tidak memerlukan token.

Respons `200 OK`:

```json
{
  "success": true,
  "service": "monitoring-service",
  "status": "UP",
  "checked_at": "2026-11-11T08:00:00+00:00"
}
```

Metode selain `GET` menghasilkan `405 Method Not Allowed` dan header `Allow: GET`.

## `GET /api/health/all`

Endpoint publik untuk memeriksa health seluruh layanan yang dikonfigurasi. Token tidak diperlukan.

Respons `200 OK`:

```json
{
  "success": true,
  "checked_at": "2026-11-11T08:00:00+00:00",
  "total_services": 4,
  "services_up": 4,
  "services_down": 1,
  "overall_status": "DEGRADED",
  "services": [
    {
      "name": "course",
      "status": "DOWN",
      "health_url": "http://HOST_COURSE:8103/api/health",
      "http_code": 0,
      "duration_ms": 3001,
      "reason": "timeout",
      "message": "Batas waktu permintaan terlampaui."
    }
  ]
}
```

Daftar `services` memuat `course`, `assignment`, `submission`, dan `notification`. Status `UP` hanya diberikan untuk respons HTTP 2xx. Timeout, kegagalan koneksi, konfigurasi yang tidak valid, dan respons non-2xx ditandai `DOWN`.

### Respons kesalahan

| HTTP | Kode | Kondisi |
|---:|---|---|
| 405 | `method_not_allowed` | Metode selain `GET`. |

## `POST /api/monitoring/events`

Endpoint untuk mencatat log peristiwa (*event*) hasil pemeriksaan atau perubahan status layanan ke penyimpanan permanen (`data/events.json`).

**Request Body (`application/json`):**

```json
{
  "service": "course",
  "status": "healthy",
  "message": "Pengecekan rutin port 8103 berhasil"
}
```
Respons 201 Created:


```json
}
  "success": true,
  "data": {
    "id": "evt-001",
    "service": "course",
    "status": "healthy",
    "message": "Pengecekan rutin port 8103 berhasil",
    "created_at": "2026-11-16T14:08:47+01:00"
  },
  "meta": {
    "request_id": "req-001"
  }
}
```

Respons kesalahan 400 Bad Request (Input kosong / tidak lengkap):

```json
{
  "success": false,
  "error": {
    "code": "invalid_input",
    "message": "Field service dan status wajib diisi dalam JSON."
  },
  "meta": {
    "request_id": "req-6afb00c924823"
  }
}
```
## GET /api/monitoring/events
Endpoint untuk mengambil seluruh daftar riwayat log peristiwa (event) yang tersimpan di dalam data/events.json.
Respons 200 OK:

```json
{
  "success": true,
  "data": [
    {
      "id": "evt-001",
      "service": "course",
      "status": "healthy",
      "message": "Pengecekan rutin port 8103 berhasil",
      "created_at": "2026-11-16T14:08:47+01:00"
    }
  ],
  "meta": {
    "request_id": "req-001"
  }
}
```

## Format Error Rute Tidak Ditemukan (404 Not Found)
Permintaan ke endpoint yang tidak terdaftar mengembalikan struktur error standar:

```json
{
  "success": false,
  "error": {
    "code": "not_found",
    "message": "Endpoint /contoh tidak ditemukan"
  },
  "meta": {
    "request_id": "req-001"
  }
}
```

## Jalur melalui Gateway

Gateway meneruskan `/api/v1/monitoring/...` ke jalur backend `/api/...` sesuai akhiran permintaan. Untuk health publik, pemetaan eksplisitnya adalah `/api/v1/monitoring/health` ke `/health`. Untuk agregat, gunakan:

```http
GET /api/v1/monitoring/all
```

Endpoint monitoring publik dan tidak memerlukan autentikasi.

## Ringkasan Skenario Pengujian (Postman)
Pengujian dilakukan menggunakan koleksi Kelompok 7 - Monitoring Service dengan environment Local (base_url = http://127.0.0.1:8107):
| No | Request | Endpoint | Skenario Uji | Status HTTP | Hasil |
|---:|---|---|---|---:|---|
| 1 | `01. Cek Health Monitoring Sendiri` | `GET /health` | Cek proses service mandiri tanpa token | `200 OK` | Sesuai kontrak (`status: "UP"`) |
| 2 | `02. Periksa Status Seluruh 7 Service` | `GET /api/monitoring/services` | Token valid & simulasi service aktif/mati | `200 OK` | Berhasil membaca status layanan |
| 5 | `03. Catat Event Monitoring` | `POST /api/monitoring/events` | Kirim payload JSON valid (`service`, `status`, `message`) | `201 Created` | Data tersimpan ke `data/events.json` |
| 6 | `03. Catat Event Monitoring` | `POST /api/monitoring/events` | Kirim payload JSON kosong `{}` | `400 Bad Request` | Ditolak dengan kode `invalid_input` |
| 7 | `03. Catat Event Monitoring` | `GET /api/monitoring/events` | Verifikasi daftar event yang tersimpan | `200 OK` | Menampilkan riwayat event (`evt-001`, dst.) |

---

## Pengujian dengan Postman (Drag & Drop)

Di dalam folder ini telah disediakan file **`postman_collection.json`** yang sudah dikhususkan untuk service ini:
1. Buka aplikasi **Postman**.
2. **Drag & drop** file `postman_collection.json` dari folder ini langsung ke jendela Postman (atau klik tombol **Import** lalu pilih file ini).
3. Seluruh endpoint pengujian milik kelompok ini akan otomatis terdaftar dan siap dijalankan tanpa perlu mengetik URL atau body secara manual.
4. Variabel `base_url` sudah otomatis diset ke port kelompok ini. Jika dijalankan antar-laptop di jaringan LAN, cukup sesuaikan nilai `base_url` dengan IP laptop tujuan.
