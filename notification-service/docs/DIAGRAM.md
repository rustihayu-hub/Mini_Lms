# Diagram Notification Service

Diagram ditulis dengan Mermaid (tampil otomatis di GitHub). Untuk tugas, ekspor juga sebagai gambar
(mis. buka https://mermaid.live, tempel kode, lalu **Download PNG**) dan simpan di `docs/bukti/`.

## 1. Hubungan antarservice

```mermaid
flowchart LR
    C[Client / Postman] --> G[API Gateway :8101]
    G --> A[Authentication :8102]
    G --> CO[Course :8103]
    G --> AS[Assignment :8104]
    G --> SU[Submission File :8105]
    G --> N[Notification :8106]
    G --> M[Monitoring :8107]

    AS -- cek course_id --> CO
    SU -- cek assignment_id --> AS
    SU -- buat notifikasi --> N
    N -- validasi token --> A
    M -. GET /health .-> N
```

## 2. Alur request: pengumpulan berhasil menghasilkan notifikasi

```mermaid
sequenceDiagram
    participant C as Client
    participant G as Gateway :8101
    participant S as Submission :8105
    participant N as Notification :8106
    participant A as Authentication :8102

    C->>G: POST /api/submissions (Bearer token)
    G->>S: teruskan request
    S->>S: validasi assignment_id
    S->>N: POST /api/notifications (Bearer token)
    N->>A: GET /api/auth/me (validasi token)
    A-->>N: 200 token valid
    N->>N: simpan notifikasi
    N-->>S: 201 Created
    S-->>G: 201 Created
    G-->>C: 201 Created
```

## 3. Penanganan kegagalan

```mermaid
flowchart TD
    R[Request masuk ke Notification] --> T{Header Bearer ada?}
    T -- tidak --> E401[401 unauthorized]
    T -- ya --> V{Authentication menjawab?}
    V -- mati --> E503[503 auth_unavailable]
    V -- lambat > 3 dtk --> E504[504 auth_timeout]
    V -- respons rusak --> E502[502 auth_bad_response]
    V -- token ditolak --> E401
    V -- token valid --> P[Proses endpoint]
    P --> OK[200 / 201 / 400 / 404 / 415]
```
