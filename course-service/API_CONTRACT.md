API CONTRACT

Kelompok 3 - Course Service

1. Informasi Layanan

* Nama Layanan: Course Service
* Kelompok: 3
* Port: 8103
* Base URL: http://127.0.0.1:8103
* Bahasa Pemrograman: PHP
* Format Data: JSON

2. Daftar Endpoint

Method	Endpoint	Fungsi
GET	/health	Memeriksa kondisi layanan
GET	/api/courses	Menampilkan daftar mata kuliah
POST	/api/courses	Menambahkan mata kuliah
DELETE	/api/courses/{id}	Menghapus mata kuliah berdasarkan ID

3. Detail Endpoint

A. Health Check

* Method: GET
* Endpoint: /health
* Fungsi: Memeriksa apakah Course Service berjalan.

Contoh Response (200 OK):

{
  "success": true,
  "message": "Course Service berjalan dengan baik"
}

B. Menampilkan Daftar Mata Kuliah

* Method: GET
* Endpoint: /api/courses
* Fungsi: Menampilkan seluruh data mata kuliah.

Contoh Response (200 OK):

{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Pemrograman Web",
      "description": "Mata kuliah dasar pemrograman website"
    }
  ]
}

Jika belum ada data mata kuliah:

{
  "success": true,
  "data": []
}

C. Menambahkan Mata Kuliah

* Method: POST
* Endpoint: /api/courses
* Fungsi: Menambahkan data mata kuliah baru.

Contoh Request Body:

{
  "name": "Pemrograman Web",
  "description": "Mata kuliah dasar pemrograman website"
}

Contoh Response (201 Created):

{
  "success": true,
  "message": "Mata kuliah berhasil ditambahkan",
  "data": {
    "id": 1,
    "name": "Pemrograman Web",
    "description": "Mata kuliah dasar pemrograman website"
  }
}

Jika nama mata kuliah tidak diisi:

Status: 400 Bad Request

{
  "success": false,
  "error": {
    "code": "INVALID_INPUT",
    "message": "Nama mata kuliah wajib diisi"
  }
}

D. Menghapus Mata Kuliah

* Method: DELETE
* Endpoint: /api/courses/{id}
* Fungsi: Menghapus mata kuliah berdasarkan ID.

Contoh Request:

DELETE /api/courses/1

Contoh Response (200 OK):

{
  "success": true,
  "message": "Mata kuliah berhasil dihapus"
}

Jika ID mata kuliah tidak ditemukan:

Status: 404 Not Found

{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Mata kuliah tidak ditemukan"
  }
}

4. HTTP Status Code

Status Code	Keterangan
200 OK	Permintaan berhasil
201 Created	Data mata kuliah berhasil ditambahkan
400 Bad Request	Input tidak valid atau nama mata kuliah kosong
404 Not Found	Endpoint atau mata kuliah tidak ditemukan

5. Teknologi yang Digunakan

* PHP
* Visual Studio Code
* XAMPP
* JSON
* Postman

6. Penyimpanan Data

Data mata kuliah disimpan menggunakan file JSON dengan lokasi:

data/courses.json

7. Keterangan

Course Service merupakan layanan yang dikembangkan oleh Kelompok 3 untuk mengelola data mata kuliah dalam sistem Mini LMS Terdistribusi.

Layanan ini menyediakan fitur pemeriksaan kesehatan layanan, menampilkan daftar mata kuliah, menambahkan mata kuliah, dan menghapus mata kuliah berdasarkan ID.

8. Catatan Pengembangan

Beberapa fitur yang masih perlu dikembangkan:

1. Implementasi autentikasi menggunakan Bearer Token.
2. Penyesuaian format respons dengan kontrak API bersama, termasuk metadata request_id.
3. Integrasi dengan API Gateway.
4. Integrasi dengan layanan lain dalam sistem Mini LMS Terdistribusi.