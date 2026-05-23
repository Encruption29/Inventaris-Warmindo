<?php
// barang_keluar.php
require_once 'koneksi.php';

$error_msg = null;
$success_msg = null;

// 1. PROSES EKSEKUSI ALGORITMA FIFO SAAT BARANG KELUAR DI-SUBMIT
if (isset($_POST['proses_barang_keluar'])) {
    $id_barang = intval($_POST['id_barang']);
    $jumlah_diminta = intval($_POST['jumlah_keluar']);
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $keterangan = $_POST['keterangan'];

    if ($id_barang > 0 && $jumlah_diminta > 0) {
        
        // Cek total stok riil gabungan yang tersedia di semua batch saat ini
        $stmt_cek = $pdo->prepare("SELECT SUM(stok_sisa) as total_stok FROM barang_masuk WHERE id_barang = :id_barang");
        $stmt_cek->execute([':id_barang' => $id_barang]);
        $total_stok_tersedia = $stmt_cek->fetch()['total_stok'] ?? 0;

        if ($jumlah_diminta > $total_stok_tersedia) {
            $error_msg = "Gagal! Stok tidak mencukupi. Stok yang tersedia saat ini hanya " . $total_stok_tersedia;
        } else {
            try {
                // MULAI DATABASE TRANSACTION (Penting untuk keamanan multi-table update)
                $pdo->beginTransaction();

                // Ambil data induk barang keluar
                $sql_insert_keluar = "INSERT INTO barang_keluar (tanggal_keluar, keterangan) VALUES (:tgl, :ket)";
                $stmt_keluar = $pdo->prepare($sql_insert_keluar);
                $stmt_keluar->execute([':tgl' => $tanggal_keluar, ':ket' => $keterangan]);
                $id_keluar = $pdo->lastInsertId();

                // AMBIL BATCH TERLAMA YANG MASIH MEMILIKI STOK (INILAH INTI FIFO)
                $sql_ambil_batch = "SELECT id_batch, stok_sisa FROM barang_masuk 
                                    WHERE id_barang = :id_barang AND stok_sisa > 0 
                                    ORDER BY tanggal_masuk ASC, id_batch ASC";
                $stmt_batch = $pdo->prepare($sql_ambil_batch);
                $stmt_batch->execute([':id_barang' => $id_barang]);
                $batches = $stmt_batch->fetchAll();

                $sisa_kebutuhan = $jumlah_diminta;

                foreach ($batches as $batch) {
                    if ($sisa_kebutuhan <= 0) break;

                    $id_batch = $batch['id_batch'];
                    $stok_batch_tersedia = $batch['stok_sisa'];

                    if ($stok_batch_tersedia >= $sisa_kebutuhan) {
                        // Kasus A: Stok di batch ini cukup untuk memenuhi semua sisa kebutuhan
                        $potong_stok = $sisa_kebutuhan;
                        $stok_baru_batch = $stok_batch_tersedia - $sisa_kebutuhan;
                        $sisa_kebutuhan = 0;
                    } else {
                        // Kasus B: Stok di batch ini tidak cukup, kuras habis batch ini, lalu lanjut ke batch berikutnya
                        $potong_stok = $stok_batch_tersedia;
                        $stok_baru_batch = 0;
                        $sisa_kebutuhan -= $stok_batch_tersedia;
                    }

                    // 1. Update sisa stok di tabel barang_masuk (Batch)
                    $sql_update_batch = "UPDATE barang_masuk SET stok_sisa = :stok_baru WHERE id_batch = :id_batch";
                    $stmt_update = $pdo->prepare($sql_update_batch);
                    $stmt_update->execute([':stok_baru' => $stok_baru_batch, ':id_batch' => $id_batch]);

                    // 2. Catat riwayat pemotongan di tabel detail_barang_keluar
                    $sql_insert_detail = "INSERT INTO detail_barang_keluar (id_keluar, id_batch, jumlah_keluar) 
                                          VALUES (:id_keluar, :id_batch, :jumlah_keluar)";
                    $stmt_detail = $pdo->prepare($sql_insert_detail);
                    $stmt_detail->execute([
                        ':id_keluar' => $id_keluar,
                        ':id_batch' => $id_batch,
                        ':jumlah_keluar' => $potong_stok
                    ]);
                }

                // Jika semua berjalan lancar, kunci data ke dalam database
                $pdo->commit();
                $success_msg = "Transaksi barang keluar berhasil diproses menggunakan metode FIFO!";
            } catch (PDOException $e) {
                // Jika ada error di tengah jalan, batalkan semua perubahan data
                $pdo->rollBack();
                $error_msg = "Terjadi kegagalan sistem FIFO: " . $e->getMessage();
            }
        }
    }
}

// 2. AMBIL MASTER BARANG UNTUK PILIHAN DROPDOWN FORM
// Kita sertakan SUM(stok_sisa) agar admin tahu sisa stok barang tersebut saat memilih
$sql_dropdown = "SELECT m.id_barang, m.nama_barang, m.satuan, IFNULL(SUM(b.stok_sisa), 0) as total_stok
                 FROM master_barang m
                 LEFT JOIN barang_masuk b ON m.id_barang = b.id_barang
                 GROUP BY m.id_barang ORDER BY m.nama_barang ASC";
$master_barang = $pdo->query($sql_dropdown)->fetchAll();

// 3. AMBIL LOG RIWAYAT BARANG KELUAR BESERTA DETAIL BATCH YANG TERPOTONG
$sql_history_keluar = "SELECT d.id_detail_keluar, k.tanggal_keluar, m.nama_barang, d.id_batch, d.jumlah_keluar, m.satuan, k.keterangan 
                       FROM detail_barang_keluar d
                       JOIN barang_keluar k ON d.id_keluar = k.id_keluar
                       JOIN barang_masuk b ON d.id_batch = b.id_batch
                       JOIN master_barang m ON b.id_barang = m.id_barang
                       ORDER BY k.tanggal_keluar DESC, d.id_detail_keluar DESC";
$history_keluar = $pdo->query($sql_history_keluar)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Keluar (FIFO) - Inventory Warmindo</title>
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
                <h2><i class="fa-solid fa-box-arrow-up text-danger me-2"></i>Penggunaan Bahan & Log Keluar</h2>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalKeluar">
                    <i class="fa-solid fa-minus me-1"></i> Input Barang Keluar
                </button>
            </div>

            <!-- Notifikasi Sistem -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-xmark me-2"></i> <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- TABEL HISTORI PENGURANGAN FIFO -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-muted"><i class="fa-solid fa-clock-rotate-left me-2"></i>Jurnal Aliran Keluar (Audit FIFO Trail)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 valign-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Waktu Keluar</th>
                                    <th>Nama Barang</th>
                                    <th class="text-center">Diambil Dari Batch</th>
                                    <th class="text-center">Jumlah Keluar</th>
                                    <th>Keterangan Keperluan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history_keluar)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada rekaman barang keluar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($history_keluar as $row): ?>
                                        <tr>
                                            <td><small><?php echo date('d-m-Y H:i', strtotime($row['tanggal_keluar'])); ?></small></td>
                                            <td><strong><?php echo htmlspecialchars($row['nama_barang']); ?></strong></td>
                                            <td class="text-center"><span class="badge bg-secondary">Batch #<?php echo $row['id_batch']; ?></span></td>
                                            <td class="text-center text-danger fw-bold">- <?php echo $row['jumlah_keluar'] . ' ' . $row['satuan']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['keterangan'] == 'Digunakan' ? 'info' : 'dark'; ?>">
                                                    <?php echo $row['keterangan']; ?>
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

        </div> <!-- End Main Content -->
    </div>
</div>

<!-- MODAL FORM INPUT BARANG KELUAR -->
<div class="modal fade" id="modalKeluar" tabindex="-1" aria-labelledby="modalKeluarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalKeluarLabel"><i class="fa-solid fa-cubes-stacked me-2"></i>Form Pengeluaran Stok</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="barang_keluar.php" method="POST">
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label for="id_barang" class="form-label fw-bold">Pilih Barang yang Digunakan <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_barang" name="id_barang" required>
                            <option value="">-- Pilih Produk (Tersedia Total Stok) --</option>
                            <?php foreach ($master_barang as $brg): ?>
                                <option value="<?php echo $brg['id_barang']; ?>" <?php echo $brg['total_stok'] == 0 ? 'disabled class="text-muted"' : ''; ?>>
                                    <?php echo htmlspecialchars($brg['nama_barang']); ?> (Tersedia: <?php echo $brg['total_stok'] . ' ' . $brg['satuan']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="jumlah_keluar" class="form-label fw-bold">Jumlah yang Dikeluarkan <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="jumlah_keluar" name="jumlah_keluar" min="1" placeholder="Masukkan jumlah unit" required>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal_keluar" class="form-label fw-bold">Waktu Pengeluaran <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-select" id="tanggal_keluar" name="tanggal_keluar" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="keterangan" class="form-label fw-bold">Keterangan / Alasan <span class="text-danger">*</span></label>
                        <select class="form-select" id="keterangan" name="keterangan" required>
                            <option value="Digunakan">Digunakan (Memasak/Dijual)</option>
                            <option value="Rusak">Rusak / Bocor / Cacat</option>
                            <option value="Kadaluwarsa">Kadaluwarsa (Expired)</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="proses_barang_keluar" class="btn btn-danger">Eksekusi FIFO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>