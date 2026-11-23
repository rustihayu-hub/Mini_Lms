# API Contract: Assignment Service (Kelompok 6)

## Informasi umum
- Port: 8104
- URL dasar: http://127.0.0.1:8104
- Format data: JSON

## Endpoint 1: Health Check
- Metode dan URL: GET /health
- Autentikasi: Tidak wajib
- Request body: -
- Respons sukses: `200 OK`
```json
{
  "success": true,
  "data": {
    "service": "assignment",
    "status": "healthy"
  },
  "meta": {
    "request_id": "req-001"
  }
}