DAFTAR KETERBATASAN IMPLEMENTASI COURSE SERVICE

Course Service Kelompok 3 telah berhasil dibuat menggunakan PHP dengan penyimpanan data berbasis file JSON.

Beberapa keterbatasan implementasi yang masih diketahui:

1. Autentikasi: Service belum menerapkan pemeriksaan Bearer Token untuk melindungi endpoint.
2. API Gateway: Integrasi dengan API Gateway belum dilakukan.
3. Format Response: Format respons belum sepenuhnya mengikuti kontrak API bersama, termasuk metadata request_id.
4. Validasi Input: Validasi input masih sederhana dan perlu ditingkatkan untuk menangani berbagai kesalahan data.
5. Pengujian Autentikasi: Pengujian tanpa token menghasilkan status 200 OK, padahal seharusnya 401 Unauthorized.
6. Monitoring: Integrasi dengan Monitoring Service belum dilakukan.

Pengujian yang Telah Dilakukan

1. Health Check: Berhasil (200 OK).
2. Menampilkan mata kuliah: Berhasil (200 OK).
3. Menambahkan mata kuliah: Berhasil (201 Created).
4. Menghapus mata kuliah: Berhasil (200 OK).
5. Input tanpa nama: Berhasil ditolak (400 Bad Request).
6. Menghapus ID yang tidak ditemukan: Berhasil ditolak (404 Not Found).
7. Mengakses layanan tanpa token: Belum sesuai harapan (200 OK, seharusnya 401 Unauthorized).

Keterbatasan tersebut akan diperbaiki secara bertahap pada tahap integrasi dan pengujian lanjutan.