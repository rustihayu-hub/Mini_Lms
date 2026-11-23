TESTING DOCUMENTATION

Mini LMS Terdistribusi - Kelompok 3

1. Informasi Pengujian

* Nama Layanan: Course Service
* Kelompok: 3
* Port: 8103
* Bahasa Pemrograman: PHP
* Tools Pengujian: Postman

2. Tujuan Pengujian

Pengujian dilakukan untuk memastikan seluruh endpoint Course Service dapat berjalan sesuai dengan fungsinya.

3. Hasil Pengujian

No.	Method	Endpoint	Fungsi	Hasil
1	GET	/health	Memeriksa kondisi layanan	Berhasil
2	GET	/api/courses	Menampilkan daftar mata kuliah	Berhasil
3	POST	/api/courses	Menambahkan mata kuliah	Berhasil
4	DELETE	/api/courses/1	Menghapus mata kuliah	Berhasil
5	GET	/api/courses	Memastikan data terhapus	Berhasil
6   DELETE   /api/courses/99999   Menghapus mata kuliah dengan ID tidak ditemukan   Berhasil (404 Not Found)
7   GET      /api/courses         Menguji akses tanpa token                       Gagal (200 OK, seharusnya 401)

4. Tools Pengujian

Pengujian API dilakukan menggunakan Postman.

5. Kesimpulan

Berdasarkan pengujian yang telah dilakukan, seluruh endpoint utama Course Service berhasil dijalankan sesuai dengan fungsi masing-masing.

Pengujian dilakukan secara mandiri dan belum mencakup integrasi dengan API Gateway.