<?php

header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

$file = __DIR__ . "/data/courses.json";

// Fungsi untuk mengirim respons JSON

function response($status, $data) {

    http_response_code($status);

    // Membuat atau menggunakan Request ID

    $requestId = $_SERVER["HTTP_X_REQUEST_ID"] ?? bin2hex(random_bytes(8));

    // Menambahkan metadata ke setiap respons

    $data["meta"] = [

        "request_id" => $requestId

    ];

    echo json_encode($data, JSON_PRETTY_PRINT);

    exit;

}

// Fungsi membaca data mata kuliah

function readCourses($file) {

    if (!file_exists($file)) {

        return [];

    }

    $data = json_decode(file_get_contents($file), true);

    return is_array($data) ? $data : [];

}

// Fungsi menyimpan data mata kuliah

function saveCourses($file, $courses) {

    file_put_contents(

        $file,

        json_encode($courses, JSON_PRETTY_PRINT)

    );

}

// 1. HEALTH CHECK

if ($uri === "/health" && $method === "GET") {

    response(200, [

        "success" => true,

        "message" => "Course Service berjalan dengan baik"

    ]);

}

// 2. MENAMPILKAN DAFTAR MATA KULIAH

if ($uri === "/api/courses" && $method === "GET") {

    $courses = readCourses($file);

    response(200, [

        "success" => true,

        "data" => $courses

    ]);

}

// 3. MENAMBAHKAN MATA KULIAH

if ($uri === "/api/courses" && $method === "POST") {

    $input = json_decode(file_get_contents("php://input"), true);

    if (!is_array($input) || empty(trim($input["name"] ?? ""))) {

        response(400, [

            "success" => false,

            "error" => [

                "code" => "INVALID_INPUT",

                "message" => "Nama mata kuliah wajib diisi"

            ]

        ]);

    }

    $courses = readCourses($file);

    $newCourse = [

        "id" => count($courses) > 0

            ? max(array_column($courses, "id")) + 1

            : 1,

        "name" => trim($input["name"]),

        "description" => trim($input["description"] ?? "")

    ];

    $courses[] = $newCourse;

    saveCourses($file, $courses);

    response(201, [

        "success" => true,

        "message" => "Mata kuliah berhasil ditambahkan",

        "data" => $newCourse

    ]);

}

// 4. MENGHAPUS MATA KULIAH

if (preg_match('#^/api/courses/([0-9]+)$#', $uri, $matches)

    && $method === "DELETE") {

    $id = (int) $matches[1];

    $courses = readCourses($file);

    $found = false;

    foreach ($courses as $index => $course) {

        if ((int) $course["id"] === $id) {

            unset($courses[$index]);

            $found = true;

            break;

        }

    }

    if (!$found) {

        response(404, [

            "success" => false,

            "error" => [

                "code" => "NOT_FOUND",

                "message" => "Mata kuliah tidak ditemukan"

            ]

        ]);

    }

    $courses = array_values($courses);

    saveCourses($file, $courses);

    response(200, [

        "success" => true,

        "message" => "Mata kuliah berhasil dihapus"

    ]);

}

// ENDPOINT TIDAK DITEMUKAN

response(404, [

    "success" => false,

    "error" => [

        "code" => "NOT_FOUND",

        "message" => "Endpoint tidak ditemukan"

    ]

]);

?>