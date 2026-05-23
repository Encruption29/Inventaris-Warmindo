<?php
// pemasok.php
require_once 'koneksi.php';

// 1. PROSES TAMBAH PEMASOK (CREATE)
if (isset($_POST['tambah_pemasok'])) {
    $nama_pemasok = trim($_POST['nama_pemasok']);
    $no_telp = trim($_POST['no_telp']);

    if (!empty($nama_pemasok)) {
        try {
            $sql = "INSERT INTO pemasok (nama_pemasok, no_telp) VALUES (:nama, :telp)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nama' => $nama_pemasok,
                ':telp' => $no_telp
            ]);
            // Redirect ke halaman yang sama untuk menghindari submit ganda saat refresh
            header("Location: pemasok.php?status=success_add");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Gagal menambah data: " . $e->getMessage();
        }
    }
}

// 2. PROSES HAPUS PEMASOK (DELETE)
if (isset($_GET['hapus'])) {
    $id_pemasok = intval($_GET['hapus']);
    try {
        $sql = "DELETE FROM pemasok WHERE id_pemasok = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_pemasok]);
        header("Location: pemasok.php?status=success_delete");
        exit;
    } catch (PDOException $e) {
        // Mencegah error jika pemasok sudah terikat dengan data di tabel barang_masuk
        $error_msg = "Pemasok tidak bisa dihapus karena data sudah digunakan pada transaksi barang masuk!";
    }
}

// 3. AMBIL DATA PEMASOK (READ)
$stmt = $pdo->query("SELECT * FROM pemasok ORDER BY id_pemasok DESC");
$data_pemasok = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pemasok - Inventory Warmindo</title>
    <!-- Jika logonya pakai file PNG di dalam folder img -->
    <link rel="icon" type="image/png" href="img/logo (1).png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <div class="col-md-3 col-lg-2 sidebar p-3 d-flex flex-column">
            <div class="text-center my-3 mx-2 p-2 rounded-3 shadow-sm" style="background-color: #fdeddf;">
                <img src="img/logo (2).png" alt="Logo Warmindo" class="img-fluid" style="max-height: 55px; object-fit: contain;">
            </div>
            <hr>
            <nav class="nav flex-column gap-1">
                <a href="index.php"><i class="fa-solid fa-gauge-high me-2"></i> Dashboard</a>
                <a href="master_barang.php"><i class="fa-solid fa-boxes-stacked me-2"></i> Master Barang</a>
                <a href="pemasok.php" class="active"><i class="fa-solid fa-truck-field me-2"></i> Data Pemasok</a>
                <!-- Menggunakan Icon Box dengan tanda panah masuk/keluar yang valid -->
                <a href="barang_masuk.php"><i class="fa-solid fa-box-open me-2"></i> Barang Masuk</a> <!-- Sistem FIFO -->
                <a href="barang_keluar.php"><i class="fa-solid fa-truck-ramp-box me-2"></i> Barang Keluar</a>
            </nav>
            <div class="mt-auto pt-3 text-center text-muted fs-7">
                <small>Warmindo Inventory &copy; 2026</small>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-truck-field text-secondary me-2"></i>Manajemen Pemasok (Supplier)</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Pemasok
                </button>
            </div>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'success_add'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> Pemasok baru berhasil ditambahkan!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'success_delete'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> Data pemasok berhasil dihapus!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 valign-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th width="80" class="text-center">ID</th>
                                    <th>Nama Pemasok / Agen</th>
                                    <th>No. Telepon / WhatsApp</th>
                                    <th width="150" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data_pemasok)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Belum ada data pemasok. Silakan tambah data baru.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data_pemasok as $pemasok): ?>
                                        <tr>
                                            <td class="text-center"><span class="badge bg-secondary">#<?php echo $pemasok['id_pemasok']; ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($pemasok['nama_pemasok']); ?></strong></td>
                                            <td>
                                                <?php if(!empty($pemasok['no_telp'])): ?>
                                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $pemasok['no_telp']); ?>" target="_blank" class="text-decoration-none">
                                                        <i class="fa-brands fa-whatsapp text-success me-1"></i> <?php echo htmlspecialchars($pemasok['no_telp']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="pemasok.php?hapus=<?php echo $pemasok['id_pemasok']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Apakah Anda yakin ingin menghapus pemasok <?php echo htmlspecialchars($pemasok['nama_pemasok']); ?>?')">
                                                    <i class="fa-solid fa-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTambahLabel"><i class="fa-solid fa-plus-circle me-2"></i>Tambah Pemasok Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-shadow="none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="pemasok.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_pemasok" class="form-label fw-bold">Nama Pemasok / Agen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_pemasok" name="nama_pemasok" placeholder="Contoh: Agen Indofood Pekanbaru / Toko Sembako Bu Sri" required>
                    </div>
                    <div class="mb-3">
                        <label for="no_telp" class="form-label fw-bold">No. Telepon / HP</label>
                        <input type="text" class="form-control" id="no_telp" name="no_telp" placeholder="Contoh: 0812XXXXXXXX">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_pemasok" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>