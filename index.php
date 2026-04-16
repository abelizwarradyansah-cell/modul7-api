<?php
$conn = new mysqli("localhost", "root", "", "db_perpustakaan");

// 1. Logika CRUD (Tambah/Edit/Hapus)
if (isset($_POST['save'])) {
    $id = $_POST['id']; $k = $_POST['kode_buku']; $j = $_POST['judul']; $p = $_POST['penulis']; 
    $pub = $_POST['penerbit']; $t = $_POST['tahun_terbit']; $kat = $_POST['kategori']; $s = $_POST['stok'];
    
    if ($id) {
        $conn->query("UPDATE buku SET judul='$j', penulis='$p', penerbit='$pub', tahun_terbit='$t', kategori='$kat', stok='$s' WHERE id=$id");
    } else {
        $conn->query("INSERT INTO buku (kode_buku,judul,penulis,penerbit,tahun_terbit,kategori,stok) VALUES ('$k','$j','$p','$pub','$t','$kat','$s')");
    }
    header("Location: index.php");
}
if (isset($_GET['hapus'])) { $conn->query("DELETE FROM buku WHERE id=".$_GET['hapus']); header("Location: index.php"); }

// 2. Logika Pencarian Pintar
$q = $_GET['q'] ?? '';
$sql = "SELECT * FROM buku WHERE 1=1";
if ($q != '') $sql .= " AND (judul LIKE '%$q%' OR penulis LIKE '%$q%' OR kategori LIKE '%$q%' OR penerbit LIKE '%$q%')";
$sql .= " ORDER BY id DESC";

$res = $conn->query($sql);
$data = []; while($r = $res->fetch_assoc()) { $data[] = $r; }
$edit = isset($_GET['edit']) ? $conn->query("SELECT * FROM buku WHERE id=".$_GET['edit'])->fetch_assoc() : null;
$categories = ["Teknologi", "Novel", "Filsafat", "Sains", "Ekonomi", "Sejarah", "Agama", "Komik"];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Manajemen Perpustakaan</title>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #7c2d12; --accent: #b45309; --bg: #fdfcf7; --card-bg: #ffffff; --text: #1c1917; --text-dim: #57534e; }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 40px; }
        .container { max-width: 1200px; margin: auto; }

        /* Header Perpustakaan */
        .lib-header { border-bottom: 2px solid var(--primary); padding-bottom: 20px; margin-bottom: 40px; }
        .lib-header h1 { font-family: 'Crimson Pro', serif; font-size: 2.8rem; margin: 0; color: var(--primary); text-transform: uppercase; letter-spacing: 2px; }

        /* Navigasi & Pencarian */
        .top-nav { display: flex; gap: 15px; margin-bottom: 30px; align-items: center; }
        .search-bar { flex: 1; background: var(--card-bg); border: 2px solid #e7e5e4; padding: 14px 20px; border-radius: 8px; color: var(--text); font-size: 1rem; outline: none; transition: 0.3s; }
        .search-bar:focus { border-color: var(--accent); background: #fff; }
        
        .btn-main { background: var(--primary); color: white; padding: 14px 28px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; font-family: 'Inter', sans-serif; }
        .btn-main:hover { background: var(--accent); }

        /* Grid Kartu Buku */
        .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }

        /* Tampilan Kartu Ala Katalog */
        .book-card { background: var(--card-bg); border-radius: 4px; border: 1px solid #e7e5e4; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: 0.3s; position: relative; display: flex; flex-direction: column; }
        .book-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border-color: var(--accent); }
        
        .book-code { font-size: 12px; font-weight: 700; color: var(--accent); margin-bottom: 5px; }
        .book-title { font-family: 'Crimson Pro', serif; font-size: 1.5rem; font-weight: 700; margin-bottom: 5px; color: var(--primary); }
        .book-author { font-size: 0.95rem; font-style: italic; color: var(--text-dim); margin-bottom: 15px; }
        
        .badge { background: #f5f5f4; color: #44403c; padding: 5px 12px; border-radius: 4px; font-size: 11px; font-weight: 600; border: 1px solid #d6d3d1; margin-right: 5px; }

        .meta-section { margin-top: auto; padding-top: 15px; border-top: 1px dotted #d6d3d1; font-size: 13px; color: var(--text-dim); }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .meta-val { font-weight: 600; color: var(--text); }

        .actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-act { flex: 1; padding: 8px; border-radius: 4px; text-align: center; text-decoration: none; font-size: 12px; font-weight: 600; border: 1px solid #d6d3d1; transition: 0.2s; }
        .edit-btn { background: #fff; color: var(--accent); border-color: var(--accent); }
        .edit-btn:hover { background: var(--accent); color: #fff; }
        .del-btn { background: #fff; color: #b91c1c; border-color: #b91c1c; }
        .del-btn:hover { background: #b91c1c; color: #fff; }

        /* Modal Input */
        .modal-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(28, 25, 23, 0.6); display: none; align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(4px); }
        .modal-content { background: #fff; width: 500px; padding: 35px; border-radius: 12px; border-top: 8px solid var(--primary); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); }
        
        .input-group { margin-bottom: 15px; }
        label { font-size: 12px; font-weight: 600; color: var(--text-dim); margin-bottom: 5px; display: block; }
        input, select { width: 100%; background: #fff; border: 1px solid #d6d3d1; padding: 12px; border-radius: 6px; box-sizing: border-box; outline: none; }
        input:focus { border-color: var(--primary); }
        
        pre { background: #f5f5f4; padding: 20px; border-radius: 8px; color: #1c1917; font-size: 12px; border: 1px solid #d6d3d1; margin-top: 50px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
    <div class="lib-header">
        <h1>PERPUSTAKAAN</h1>
        <p style="color: var(--text-dim); margin-top: 10px; font-weight: 500;">Koleksi buku digital</p>
    </div>

    <div class="top-nav">
        <form method="GET" style="flex:1">
            <input type="text" name="q" class="search-bar" placeholder="Cari judul, penulis, atau penerbit..." value="<?= htmlspecialchars($q) ?>" onchange="this.form.submit()">
        </form>
        <button class="btn-main" onclick="openModal()">+ Tambah Buku Baru</button>
    </div>

    <div class="book-grid">
        <?php foreach($data as $r): ?>
        <div class="book-card">
            <div class="card-header">
                <div class="book-code">KODE: <?= $r['kode_buku'] ?></div>
                <div class="book-title"><?= $r['judul'] ?></div>
                <div class="book-author">Karya: <?= $r['penulis'] ?></div>
                <div style="margin-bottom: 15px;">
                    <span class="badge"><?= $r['kategori'] ?></span>
                    <span class="badge">Stok: <?= $r['stok'] ?></span>
                </div>
            </div>
            
            <div class="meta-section">
                <div class="meta-row"><span>Penerbit:</span> <span class="meta-val"><?= $r['penerbit'] ?></span></div>
                <div class="meta-row"><span>Tahun Terbit:</span> <span class="meta-val"><?= $r['tahun_terbit'] ?></span></div>
                
                <div class="actions">
                    <a href="index.php?edit=<?= $r['id'] ?>" class="btn-act edit-btn">Ubah Data</a>
                    <a href="index.php?hapus=<?= $r['id'] ?>" class="btn-act del-btn" onclick="return confirm('Hapus data buku ini?')">Hapus</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 60px; color: var(--text-dim); font-size: 12px; font-weight: 700; border-bottom: 1px solid #d6d3d1; padding-bottom: 10px; margin-bottom: 15px;">API LIVE STREAM (JSON)</div>
    <pre><?= json_encode(["status" => "sukses", "data" => $data], JSON_PRETTY_PRINT); ?></pre>
</div>

<div class="modal-overlay" id="modalInput" style="<?= $edit ? 'display:flex' : '' ?>">
    <div class="modal-content">
        <h3 style="margin-top:0; font-family: 'Crimson Pro', serif; font-size: 1.8rem; color: var(--primary);"><?= $edit ? "Ubah Informasi Buku" : "Input Koleksi Baru" ?></h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
            <div class="input-group">
                <label>KODE BUKU</label>
                <input type="text" name="kode_buku" placeholder="Contoh: BK-01" value="<?= $edit['kode_buku'] ?? '' ?>" required <?= $edit ? 'readonly' : '' ?>>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="input-group" style="flex:2"><label>JUDUL BUKU</label><input type="text" name="judul" value="<?= $edit['judul'] ?? '' ?>" required></div>
                <div class="input-group" style="flex:1"><label>TAHUN</label><input type="number" name="tahun_terbit" value="<?= $edit['tahun_terbit'] ?? '2024' ?>" required></div>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="input-group" style="flex:1"><label>PENULIS</label><input type="text" name="penulis" value="<?= $edit['penulis'] ?? '' ?>" required></div>
                <div class="input-group" style="flex:1"><label>PENERBIT</label><input type="text" name="penerbit" value="<?= $edit['penerbit'] ?? '' ?>" required></div>
            </div>
            <div style="display: flex; gap: 15px;">
                <div class="input-group" style="flex: 2;"><label>KATEGORI</label>
                    <select name="kategori">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($edit && $edit['kategori'] == $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group" style="flex: 1;"><label>STOK</label><input type="number" name="stok" value="<?= $edit['stok'] ?? '1' ?>"></div>
            </div>
            <button type="submit" name="save" class="btn-main" style="width: 100%; margin-top: 10px;"><?= $edit ? "SIMPAN PERUBAHAN" : "MASUKKAN KE RAK" ?></button>
            <center><a href="index.php" style="color:var(--text-dim); font-size:13px; text-decoration:none; display:block; margin-top:20px; font-weight: 600;">BATALKAN</a></center>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('modalInput').style.display = 'flex'; }
</script>2

</body>
</html>