<?php
session_start();
include '../config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_keranjang'])) {
    $id_produk = $_POST['id_produk'];
    $nama      = $_POST['nama'];
    $harga     = $_POST['harga'];

    $cek = mysqli_query($koneksi, "SELECT stok FROM produk WHERE id = '$id_produk'");
    $data = mysqli_fetch_assoc($cek);
    $stok = $data['stok'] ?? 0;

    $jumlah_di_keranjang = 0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        if ($item['id'] == $id_produk) {
            $jumlah_di_keranjang += $item['jumlah'];
        }
    }

    if ($jumlah_di_keranjang < $stok) {
        $found = false;
        foreach ($_SESSION['cart'] ?? [] as &$item) {
            if ($item['id'] == $id_produk) {
                $item['jumlah'] += 1;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $_SESSION['cart'][] = [
                'id'     => $id_produk,
                'nama'   => $nama,
                'harga'  => $harga,
                'jumlah' => 1
            ];
        }
    } else {
        echo "<script>alert('⚠ Stok tidak mencukupi untuk $nama');</script>";
    }

    header("Location: toko.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_komentar'])) {
    $produk = $_POST['produk'];
    $username = $_SESSION['username'];
    $komentar = htmlspecialchars($_POST['komentar']);

    mysqli_query($koneksi, "INSERT INTO komentar (nama, nama_produk, isi) 
                            VALUES ('$username', '$produk', '$komentar')");
    header("Location: toko.php");
    exit();
}

$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Toko Crumble Bii</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="../user/assets/image.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../user/css/toko.css">
  <style>
    .card-title { font-weight: 600; }
    .card-text { min-height: 50px; }
    .badge.bg-dark { font-size: 0.75rem; }
    .card-body form .form-control { border-radius: 0.25rem; }
    .input-group-sm .form-control { height: calc(1.5em + .5rem + 2px); }
    .profil-foto {
      width: 100px; height: 100px; object-fit: cover;
      border-radius: 50%; margin-bottom: 15px;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top shadow-sm bg-white">
  <div class="container">
    <a class="navbar-brand fw-bold text-danger" href="#">Crumble Bii</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
        <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
      </ul>
      <form class="d-flex me-2" method="get" action="#menu">
        <input class="form-control me-2" id="searchBar" name="cari" type="search" placeholder="Cari produk..." aria-label="Search">
        <button class="btn btn-outline-dark" type="submit">Cari</button>
      </form>
      <a href="cart.php" class="btn btn-outline-dark me-2 position-relative">
        <i class="bi bi-cart-fill"></i>
        <span class="badge bg-danger text-white position-absolute top-0 start-100 translate-middle rounded-pill"><?= $cart_count ?></span>
      </a>
      <button class="btn btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#modalProfil">
        <i class="bi bi-person-circle"></i> Profil
      </button>
      <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>
  </div>
</nav>

<header class="text-center mt-5 pt-5">
  <div class="container">
    <h1 class="display-5 fw-bold">Cemilan Modern Crumble Bii</h1>
    <p class="lead">Crunchy di luar, lembut di dalam</p>
  </div>
</header>

<section id="menu" class="py-5">
  <div class="container">
    <h2 class="text-center mb-4">Menu Kami</h2>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
      <?php
      $keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : '';
      $query = mysqli_query($koneksi, "SELECT produk.*, kategori.nama_kategori
                                       FROM produk
                                       JOIN kategori ON produk.id_kategori = kategori.id
                                       WHERE produk.nama_produk LIKE '%$keyword%'
                                       ORDER BY produk.id DESC");
      while ($item = mysqli_fetch_assoc($query)):
      ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <img src="../uploads/<?= $item['gambar'] ?>" class="card-img-top" alt="<?= $item['nama_produk'] ?>" style="height: 220px; object-fit: cover;">
          <div class="card-body text-center">
            <h5 class="card-title"><?= $item['nama_produk'] ?></h5>
            <p class="card-text"><?= $item['deskripsi'] ?></p>
            <p class="fw-bold text-success">Rp <?= number_format($item['harga'], 0, ',', '.') ?> / 1pcs</p>
            <p class="text-muted small">Stok: <?= $item['stok'] ?> | Kategori: <?= $item['nama_kategori'] ?></p>

            <?php if ($item['stok'] > 0): ?>
              <form method="post" class="mb-2">
                <input type="hidden" name="id_produk" value="<?= $item['id'] ?>">
                <input type="hidden" name="nama" value="<?= $item['nama_produk'] ?>">
                <input type="hidden" name="harga" value="<?= $item['harga'] ?>">
                <button type="submit" name="tambah_keranjang" class="btn btn-outline-dark w-100">
                  <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                </button>
              </form>
            <?php else: ?>
              <button class="btn btn-danger w-100" disabled>Stok Habis</button>
            <?php endif; ?>

            <form method="post">
              <input type="hidden" name="produk" value="<?= $item['nama_produk'] ?>">
              <div class="input-group input-group-sm mb-2">
                <input type="text" name="komentar" class="form-control" placeholder="Tulis komentar..." required>
                <button type="submit" name="kirim_komentar" class="btn btn-danger">Kirim</button>
              </div>
            </form>

            <div class="text-start small">
              <strong>Komentar:</strong>
              <?php
              $nama_produk = mysqli_real_escape_string($koneksi, $item['nama_produk']);
              $queryKomen = mysqli_query($koneksi, "SELECT * FROM komentar WHERE nama_produk = '$nama_produk' ORDER BY created_at DESC LIMIT 3");
              while ($kmt = mysqli_fetch_assoc($queryKomen)) {
                  echo "<div class='border-bottom py-1'><b>{$kmt['nama']}</b>: {$kmt['isi']}</div>";
              }
              ?>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<section id="kontak" class="py-5 text-center">
  <div class="container">
    <h2 class="mb-4">Hubungi Kami</h2>
    <p>📍 Jl. Hangtuah No. 20, Pekanbaru</p>
    <p>⏰ Setiap hari (09.00 – 21.00)</p>
    <div class="d-flex justify-content-center gap-3 flex-wrap mt-3">
      <a href="https://wa.me/6281276852821" class="btn btn-success" target="_blank">
        <i class="bi bi-whatsapp"></i> WhatsApp
      </a>
      <a href="https://instagram.com/crumblebii_pku" class="btn btn-danger" target="_blank">
        <i class="bi bi-instagram"></i> Instagram
      </a>
      <a href="https://maps.app.goo.gl/DvhcmrDRk9VjzWSVA?g_st=aw" class="btn btn-secondary" target="_blank">
        <i class="bi bi-geo-alt-fill"></i> Lokasi
      </a>
    </div>
  </div>
</section>

<footer class="py-4 bg-dark text-white text-center">
  <div class="container">
    <p class="mb-0">© 2025 Kewirausahaan</p>
  </div>
</footer>

<div class="modal fade" id="modalProfil" tabindex="-1" aria-labelledby="modalProfilLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-center">
      <?php
      $username = $_SESSION['username'];
      $q = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username'");
      $profil = mysqli_fetch_assoc($q);
      $foto = !empty($profil['foto']) ? "../uploads/{$profil['foto']}" : "https://ui-avatars.com/api/?name=" . urlencode($profil['nama']);
      ?>
      <div class="modal-header">
        <h5 class="modal-title" id="modalProfilLabel">Profil Pengguna</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <img src="<?= $foto ?>" alt="Foto Profil" class="profil-foto">
        <h5 class="mb-1"><?= $profil['nama'] ?></h5>
        <p class="mb-1"><strong>Username:</strong> <?= $profil['username'] ?></p>
        <p class="mb-1"><strong>Role:</strong> <?= ucfirst($profil['role']) ?></p>
        <p class="mb-1"><strong>Level:</strong> <?= $profil['level'] ?? '-' ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
