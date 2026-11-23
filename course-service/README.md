COURSE SERVICE

Mini LMS Terdistribusi - Kelompok 3

1. Deskripsi

Course Service merupakan layanan untuk mengelola data mata kuliah dalam sistem Mini LMS Terdistribusi.

Layanan ini menyediakan fitur untuk menampilkan, menambahkan, dan menghapus data mata kuliah.

2. Anggota Kelompok

Kelompok: 3

Nama anggota:

* (Nur Alifia Azis)
* (Ririn Anggriani Ilham)
* (Rahmat Syawal)
* (Achmad Fauzi)
* (Muh Al Ladising)

3. Teknologi yang Digunakan

* PHP
* XAMPP
* Visual Studio Code
* JSON
* Postman

4. Port Layanan

* Port: 8103
* Base URL: http://127.0.0.1:8103

5. Cara Menjalankan

1. Buka folder proyek di Visual Studio Code.
2. Buka Terminal.
3. Jalankan perintah:

php -S 127.0.0.1:8103 index.php

4. Buka browser dan akses:

http://127.0.0.1:8103/health

6. Daftar Fitur

1. Memeriksa kondisi layanan.
2. Menampilkan daftar mata kuliah.
3. Menambahkan mata kuliah.
4. Menghapus mata kuliah berdasarkan ID.

7. Daftar Endpoint

Method	Endpoint	Fungsi
GET	/health	Memeriksa kondisi layanan
GET	/api/courses	Menampilkan daftar mata kuliah
POST	/api/courses	Menambahkan mata kuliah
DELETE	/api/courses/{id}	Menghapus mata kuliah

8. Penyimpanan Data

Data mata kuliah disimpan dalam file:

data/courses.json

9. Dokumentasi dan Pengujian

* API_CONTRACT.md: Dokumentasi kontrak API.
* TESTING.md: Dokumentasi hasil pengujian.
* postman/: Postman Collection.
* docs/bukti/: Bukti screenshot pengujian.
* docs/DIAGRAM.md: Diagram hubungan layanan.
* docs/LIMITATIONS.md: Daftar keterbatasan implementasi.

10. Keterbatasan Implementasi

Beberapa fitur masih dalam tahap pengembangan:

1. Autentikasi menggunakan Bearer Token.
2. Integrasi dengan API Gateway.
3. Penyesuaian format respons dengan kontrak API bersama.
4. Integrasi dengan layanan lainnya.

11. Kesimpulan

Course Service Kelompok 3 telah berhasil dibuat menggunakan PHP dengan penyimpanan data berbasis JSON. Pengujian dasar endpoint telah dilakukan menggunakan Postman.