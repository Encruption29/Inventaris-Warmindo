<?php
// master_barang.php
require_once 'koneksi.php';

// AMBIL SEMUA DATA MASTER BARANG DAN HITUNG STOK RIIL DARI BATCH FIFO
$sql = "SELECT m.*, IFNULL(SUM(b.stok_sisa), 0) as stok_sekarang 
        FROM master_barang m 
        LEFT JOIN barang_masuk b ON m.id_barang = b.id_barang 
        GROUP BY m.id_barang 
        ORDER BY m.kategori ASC, m.nama_barang ASC";
$stmt = $pdo->query($sql);
$data_barang = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Barang - Inventory Warmindo</title>
    <!-- Jika logonya pakai file PNG di dalam folder img -->
    <link rel="icon" type="image/png" href="img/logo (1).png">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome untuk Icon -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #343a40; min-height: 100vh; color: white; }
        .sidebar a { color: #c2c7d0; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #495057; color: white; border-radius: 4px; }
        .badge-kategori { font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR NAVIGASI -->
        <div class="col-md-3 col-lg-2 sidebar p-3 d-flex flex-column">
            <div class="text-center my-3 mx-2 p-2 rounded-3 shadow-sm" style="background-color: #fdeddf;">
                <img src="img/logo (2).png" alt="Logo Warmindo" class="img-fluid" style="max-height: 55px; object-fit: contain;">
            </div>
            <hr>
            <nav class="nav flex-column gap-1">
                <a href="index.php"><i class="fa-solid fa-gauge-high me-2"></i> Dashboard</a>
                <a href="master_barang.php" class="active"><i class="fa-solid fa-boxes-stacked me-2"></i> Master Barang</a>
                <a href="pemasok.php"><i class="fa-solid fa-truck-field me-2"></i> Data Pemasok</a>
                <!-- Menggunakan Icon Box dengan tanda panah masuk/keluar yang valid -->
                <a href="barang_masuk.php"><i class="fa-solid fa-box-open me-2"></i> Barang Masuk</a> <!-- Sistem FIFO -->
                <a href="barang_keluar.php"><i class="fa-solid fa-truck-ramp-box me-2"></i> Barang Keluar</a>
            </nav>
            <div class="mt-auto pt-3 text-center text-muted fs-7">
                <small>Warmindo Inventory &copy; 2026</small>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Katalog Master Barang (30 Items)</h2>
                <span class="badge bg-primary p-2">Total: 30 Komoditas</span>
            </div>

            <!-- TABEL MASTER BARANG -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-muted"><i class="fa-solid fa-folder-open me-2"></i>Daftar Inventaris Sesuai Kategori</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 valign-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th width="60" class="text-center">No</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th class="text-center">Stok Minimal</th>
                                    <th class="text-center">Stok Aktif Saat Ini</th>
                                    <th>Satuan</th>
                                    <th>Status Gudang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($data_barang as $row): 
                                    // Tentukan warna badge berdasarkan status stok
                                    if ($row['stok_sekarang'] == 0) {
                                        $status_badge = '<span class="badge bg-danger">Habis Total</span>';
                                        $stok_class = 'text-danger fw-bold';
                                    } elseif ($row['stok_sekarang'] <= $row['stok_minimal']) {
                                        $status_badge = '<span class="badge bg-warning text-dark">Butuh Restock</span>';
                                        $stok_class = 'text-warning fw-bold';
                                    } else {
                                        $status_badge = '<span class="badge bg-success">Stok Aman</span>';
                                        $stok_class = 'text-success fw-bold';
                                    }

                                    // Mengatur warna badge kategori agar bervariasi
                                    $kat = $row['kategori'];
                                    $bg_kat = 'bg-secondary';
                                    if ($kat == 'Mie Instan') $bg_kat = 'bg-danger';
                                    if ($kat == 'Topping/Frozen Food') $bg_kat = 'bg-info text-dark';
                                    if ($kat == 'Bumbu & Sayur') $bg_kat = 'bg-success';
                                    if ($kat == 'Minuman') $bg_kat = 'bg-primary';
                                ?>
                                    <tr>
                                        <td class="text-center text-muted"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['nama_barang']); ?></strong></td>
                                        <td><span class="badge <?php echo $bg_kat; ?> badge-kategori"><?php echo $row['kategori']; ?></span></td>
                                        <td class="text-center"><?php echo $row['stok_minimal']; ?></td>
                                        <td class="text-center <?php echo $stok_class; ?>"><?php echo $row['stok_sekarang']; ?></td>
                                        <td><small class="text-muted"><?php echo $row['satuan']; ?></small></td>
                                        <td><?php echo $status_badge; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- End Main Content -->
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>