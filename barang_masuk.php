<?php
// barang_masuk.php
require_once 'koneksi.php';

// 1. PROSES INPUT BARANG MASUK (BATCH FIFO)
if (isset($_POST['tambah_barang_masuk'])) {
    $id_barang = intval($_POST['id_barang']);
    $id_pemasok = intval($_POST['id_pemasok']);
    $jumlah_masuk = intval($_POST['jumlah_masuk']);
    $harga_beli = floatval($_POST['harga_beli']);
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $tanggal_kadaluwarsa = !empty($_POST['tanggal_kadaluwarsa']) ? $_POST['tanggal_kadaluwarsa'] : null;

    if ($id_barang > 0 && $jumlah_masuk > 0) {
        try {
            // Untuk FIFO baru, stok_sisa nilainya SAMA dengan jumlah_masuk awal
            $sql = "INSERT INTO barang_masuk (id_barang, id_pemasok, jumlah_masuk, stok_sisa, harga_beli, tanggal_masuk, tanggal_kadaluwarsa) 
                    VALUES (:id_barang, :id_pemasok, :jumlah_masuk, :stok_sisa, :harga_beli, :tanggal_masuk, :tanggal_kadaluwarsa)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id_barang' => $id_barang,
                ':id_pemasok' => $id_pemasok,
                ':jumlah_masuk' => $jumlah_masuk,
                ':stok_sisa' => $jumlah_masuk, // Kunci utama manajemen batch FIFO
                ':harga_beli' => $harga_beli,
                ':tanggal_masuk' => $tanggal_masuk,
                ':tanggal_kadaluwarsa' => $tanggal_kadaluwarsa
            ]);

            header("Location: barang_masuk.php?status=success");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Gagal mencatat transaksi: " . $e->getMessage();
        }
    }
}

// 2. AMBIL DATA MASTER BARANG & PEMASOK UNTUK FORM DROPDOWN
$master_barang = $pdo->query("SELECT id_barang, nama_barang, satuan FROM master_barang ORDER BY nama_barang ASC")->fetchAll();
$pemasok_list = $pdo->query("SELECT id_pemasok, nama_pemasok FROM pemasok ORDER BY nama_pemasok ASC")->fetchAll();

// 3. AMBIL HISTORY BARANG MASUK (BATCH LOG FIFO)
$sql_history = "SELECT b.*, m.nama_barang, m.satuan, p.nama_pemasok 
                FROM barang_masuk b
                JOIN master_barang m ON b.id_barang = m.id_barang
                LEFT JOIN pemasok p ON b.id_pemasok = p.id_pemasok
                ORDER BY b.tanggal_masuk DESC, b.id_batch DESC";
$history_masuk = $pdo->query($sql_history)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Masuk (FIFO) - Inventory Warmindo</title>
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
                <h2><i class="fa-solid fa-box-arrow-in-down text-success me-2"></i>Log Restock & Batch FIFO Masuk</h2>
                <!-- Tombol Trigger Modal Tambah -->
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMasuk">
                    <i class="fa-solid fa-plus me-1"></i> Input Barang Masuk
                </button>
            </div>

            <!-- Notifikasi Status -->
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> Batch FIFO Baru berhasil didaftarkan ke sistem!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- TABEL HISTORI BATCH MASUK -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-muted"><i class="fa-solid fa-layer-group me-2"></i>Daftar Aktivitas Aliran Batch</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 valign-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center">Batch ID</th>
                                    <th>Tanggal Masuk</th>
                                    <th>Nama Barang</th>
                                    <th>Pemasok</th>
                                    <th class="text-center">Jumlah Awal</th>
                                    <th class="text-center">Sisa Stok Batch</th>
                                    <th>Harga Beli Unit</th>
                                    <th>Status FIFO</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history_masuk)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Belum ada aktivitas barang masuk terdaftar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($history_masuk as $row): ?>
                                        <tr>
                                            <td class="text-center"><span class="badge bg-secondary">#<?php echo $row['id_batch']; ?></span></td>
                                            <td><small><?php echo date('d-m-Y H:i', strtotime($row['tanggal_masuk'])); ?></small></td>
                                            <td><strong><?php echo htmlspecialchars($row['nama_barang']); ?></strong></td>
                                            <td><small class="text-muted"><?php echo htmlspecialchars($row['nama_pemasok'] ?? 'Tanpa Pemasok'); ?></small></td>
                                            <td class="text-center"><?php echo $row['jumlah_masuk'] . ' ' . $row['satuan']; ?></td>
                                            <td class="text-center fw-bold text-primary"><?php echo $row['stok_sisa'] . ' ' . $row['satuan']; ?></td>
                                            <td>Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                                            <td>
                                                <?php if ($row['stok_sisa'] == 0): ?>
                                                    <span class="badge bg-secondary opacity-75">Habis (Clear)</span>
                                                <?php elseif ($row['stok_sisa'] < $row['jumlah_masuk']): ?>
                                                    <span class="badge bg-warning text-dark">Terpakai Sebagian</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Utuh (Antrean)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- End Main Content -->
    </div>
</div>

<!-- MODAL FORM INPUT BARANG MASUK -->
<div class="modal fade" id="modalMasuk" tabindex="-1" aria-labelledby="modalMasukLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalMasukLabel"><i class="fa-solid fa-boxes-packing me-2"></i>Form Transaksi Belanja / Masuk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="barang_masuk.php" method="POST">
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label for="id_barang" class="form-label fw-bold">Pilih Item Barang <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_barang" name="id_barang" required>
                            <option value="">-- Pilih dari 30 Master Barang Warmindo --</option>
                            <?php foreach ($master_barang as $brg): ?>
                                <option value="<?php echo $brg['id_barang']; ?>">
                                    <?php echo htmlspecialchars($brg['nama_barang']); ?> (<?php echo $brg['satuan']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="id_pemasok" class="form-label fw-bold">Pemasok / Supplier</label>
                        <select class="form-select" id="id_pemasok" name="id_pemasok">
                            <option value="">-- Pilih Pemasok (Opsional) --</option>
                            <?php foreach ($pemasok_list as $pms): ?>
                                <option value="<?php echo $pms['id_pemasok']; ?>">
                                    <?php echo htmlspecialchars($pms['nama_pemasok']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="jumlah_masuk" class="form-label fw-bold">Jumlah Kuantitas <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="jumlah_masuk" name="jumlah_masuk" min="1" placeholder="Mulai dari 1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="harga_beli" class="form-label fw-bold">Harga Beli / Unit (Rp) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="harga_beli" name="harga_beli" min="0" placeholder="Contoh: 3000" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal_masuk" class="form-label fw-bold">Waktu Transaksi Masuk <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-select" id="tanggal_masuk" name="tanggal_masuk" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal_kadaluwarsa" class="form-label fw-bold">Tanggal Kadaluwarsa <small class="text-muted">(Khusus Topping/Frozen)</small></label>
                        <input type="date" class="form-control" id="tanggal_kadaluwarsa" name="tanggal_kadaluwarsa">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_barang_masuk" class="btn btn-success">Daftarkan Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>