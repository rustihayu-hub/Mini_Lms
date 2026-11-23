DIAGRAM HUBUNGAN LAYANAN

Mini LMS Terdistribusi - Kelompok 3

flowchart TD
    A[Client / Postman] --> B[API Gateway]
    B --> C[Course Service - Kelompok 3]
    C --> D[Data courses.json]
    E[Assignment Service] -. Membutuhkan data Course .-> C
    F[Monitoring Service] -. Memeriksa kesehatan .-> C
    F --> G[GET /health]
    G --> C

Keterangan

1. Client mengirimkan permintaan melalui API Gateway.
2. API Gateway meneruskan permintaan ke Course Service.
3. Course Service mengelola data mata kuliah.
4. Data mata kuliah disimpan dalam file courses.json.
5. Assignment Service dapat membutuhkan data dari Course Service.
6. Monitoring Service memeriksa kondisi Course Service melalui endpoint GET /health.

Catatan: Diagram ini merupakan gambaran rancangan hubungan layanan. Arah komunikasi dan integrasinya perlu disesuaikan dengan implementasi kelompok terkait.