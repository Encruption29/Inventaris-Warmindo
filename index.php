<?php
// index.php
require_once 'koneksi.php';

// 1. QUERY UNTUK WIDGET SUMMARY
// Menghitung total jenis barang
$stmt_total_barang = $pdo->query("SELECT COUNT(*) as total FROM master_barang");
$total_barang = $stmt_total_barang->fetch()['total'];

// Menghitung total stok riil saat ini (Sum dari stok_sisa di batch FIFO)
$stmt_total_stok = $pdo->query("SELECT SUM(stok_sisa) as total FROM barang_masuk");
$total_stok = $stmt_total_stok->fetch()['total'] ?? 0;

// Menghitung jumlah pemasok
$stmt_total_pemasok = $pdo->query("SELECT COUNT(*) as total FROM pemasok");
$total_pemasok = $stmt_total_pemasok->fetch()['total'];


// 2. QUERY ALERT: STOK KRITIS
// Menggabungkan master_barang dengan total sisa stok dari batch untuk melihat yang di bawah limit
$sql_kritis = "SELECT m.nama_barang, m.satuan, m.stok_minimal, IFNULL(SUM(b.stok_sisa), 0) as stok_sekarang 
               FROM master_barang m 
               LEFT JOIN barang_masuk b ON m.id_barang = b.id_barang 
               GROUP BY m.id_barang 
               HAVING stok_sekarang <= m.stok_minimal";
$stmt_kritis = $pdo->query($sql_kritis);
$data_kritis = $stmt_kritis->fetchAll();


// 3. QUERY ALERT: KEDALUWARSA (Mendekati kedaluwarsa dalam 30 hari ke depan & stok masih ada)
$sql_expired = "SELECT b.id_batch, m.nama_barang, b.stok_sisa, b.tanggal_kadaluwarsa 
                FROM barang_masuk b 
                JOIN master_barang m ON b.id_barang = m.id_barang 
                WHERE b.stok_sisa > 0 AND b.tanggal_kadaluwarsa <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ORDER BY b.tanggal_kadaluwarsa ASC";
$stmt_expired = $pdo->query($sql_expired);
$data_expired = $stmt_expired->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Inventory Warmindo</title>
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
        .card-widget { border: none; border-radius: 10px; transition: transform 0.2s; }
        .card-widget:hover { transform: translateY(-5px); }
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
                <a href="index.php" class="active"><i class="fa-solid fa-gauge-high me-2"></i> Dashboard</a>
                <a href="master_barang.php"><i class="fa-solid fa-boxes-stacked me-2"></i> Master Barang</a>
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
                <h2>Dashboard Monitoring Inventaris</h2>
                <span class="badge bg-secondary p-2"><i class="fa-solid fa-calendar-days me-1"></i> <?php echo date('d M Y'); ?></span>
            </div>

            <!-- 1. WIDGET SUMMARY CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-widget bg-primary text-white p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3><?php echo $total_barang; ?></h3>
                                <p class="mb-0">Total Item Barang</p>
                            </div>
                            <i class="fa-solid fa-boxes-stacked fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-widget bg-success text-white p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3><?php echo $total_stok; ?></h3>
                                <p class="mb-0">Total Stok di Gudang</p>
                            </div>
                            <i class="fa-solid fa-warehouse fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-widget bg-info text-white p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3><?php echo $total_pemasok; ?></h3>
                                <p class="mb-0">Pemasok Terdaftar</p>
                            </div>
                            <i class="fa-solid fa-handshake fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- 2. TABEL ALERT STOK KRITIS -->
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-danger text-white d-flex align-items-center">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <h5 class="mb-0 card-title flex-grow-1">Peringatan: Stok Kritis / Habis</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 valign-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th class="text-center">Minimal</th>
                                            <th class="text-center">Stok Sisa</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data_kritis)): ?>
                                            <tr><td colspan="4" class="text-center py-3 text-muted">Semua stok barang aman.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($data_kritis as $kritis): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($kritis['nama_barang']); ?></strong></td>
                                                    <td class="text-center"><?php echo $kritis['stok_minimal'] . ' ' . $kritis['satuan']; ?></td>
                                                    <td class="text-center text-danger fw-bold"><?php echo $kritis['stok_sekarang'] . ' ' . $kritis['satuan']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $kritis['stok_sekarang'] == 0 ? 'danger' : 'warning text-dark'; ?>">
                                                            <?php echo $kritis['stok_sekarang'] == 0 ? 'Habis' : 'Restock'; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. TABEL ALERT KEDALUWARSA -->
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-warning text-dark d-flex align-items-center">
                            <i class="fa-solid fa-hourglass-half me-2"></i>
                            <h5 class="mb-0 card-title flex-grow-1">Peringatan: Batch Dekat Kedaluwarsa</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0 valign-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Batch ID</th>
                                            <th>Nama Barang</th>
                                            <th class="text-center">Sisa Stok</th>
                                            <th>Tgl Kadaluwarsa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data_expired)): ?>
                                            <tr><td colspan="4" class="text-center py-3 text-muted">Tidak ada produk yang mendekati kedaluwarsa.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($data_expired as $exp): ?>
                                                <tr>
                                                    <td><span class="badge bg-secondary">#<?php echo $exp['id_batch']; ?></span></td>
                                                    <td><strong><?php echo htmlspecialchars($exp['nama_barang']); ?></strong></td>
                                                    <td class="text-center"><?php echo $exp['stok_sisa']; ?></td>
                                                    <td class="text-danger fw-bold">
                                                        <?php echo date('d-m-Y', strtotime($exp['tanggal_kadaluwarsa'])); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- End Row -->

        </div> <!-- End Main Content -->
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>