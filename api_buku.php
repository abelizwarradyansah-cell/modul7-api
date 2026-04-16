<?php
header("Content-Type: application/json");
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_perpustakaan";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["error" => "Koneksi Gagal"]));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $sql = "SELECT * FROM buku WHERE 1=1";

    // 1. Berdasarkan ID
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $sql .= " AND id = '$id'";
    }

    // 2. Berdasarkan Kategori
    if (isset($_GET['kategori'])) {
        $kategori = $_GET['kategori'];
        $sql .= " AND kategori = '$kategori'";
    }

    // 3. Cari berdasarkan Judul (LIKE)
    if (isset($_GET['judul'])) {
        $judul = $_GET['judul'];
        $sql .= " AND judul LIKE '%$judul%'";
    }

    // 4. Berdasarkan Rentang Tahun (tahun_mulai & tahun_akhir)
    if (isset($_GET['tahun_mulai']) && isset($_GET['tahun_akhir'])) {
        $mulai = $_GET['tahun_mulai'];
        $akhir = $_GET['tahun_akhir'];
        $sql .= " AND tahun_terbit BETWEEN '$mulai' AND '$akhir'";
    }

    // 5. Berdasarkan Minimal Stok
    if (isset($_GET['stok_min'])) {
        $stok_min = $_GET['stok_min'];
        $sql .= " AND stok >= '$stok_min'";
    }

    $result = $conn->query($sql);
    $data = array();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Data tidak ditemukan"]);
    }
}
$conn->close();
?>