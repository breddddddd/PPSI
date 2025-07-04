<?php
session_start();
include '../config/config.php'; // Pastikan path ini benar untuk koneksi database

// --- DEBUGGING: Aktifkan pelaporan error maksimal ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- AKHIR DEBUGGING ---

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'penjual' || !isset($_GET['id'])) {
  header("Location: sellerproducts.php");
  exit();
}

$product_id = $_GET['id'];
$seller_id = $_SESSION['user_id'];

// Ambil data produk yang akan diedit dari database
$stmt = $conn->prepare("SELECT id, name, category, description, price, image, stock, status, variant FROM products WHERE id = ? AND seller_id = ?");
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// --- DEBUG: Periksa tipe kolom 'image' yang dilihat oleh MySQLi ---
// Ini harus ditempatkan setelah $stmt->execute() dan $result->fetch_assoc()
// dan sebelum $stmt->close() yang terkait dengan SELECT pertama
$stmt_meta = $stmt->result_metadata();
if ($stmt_meta) {
    $fields = $stmt_meta->fetch_fields();
    foreach ($fields as $field) {
        if ($field->name === 'image') {
            echo "<pre style='background-color:#ffeeff; padding:10px; border:1px solid #ffccff;'>";
            echo "DEBUG: MySQLi Column Type for 'image': ";
            var_dump($field->type); // Ini akan mengeluarkan kode numerik untuk tipe data
            echo "DEBUG: MySQLi Flags for 'image': ";
            var_dump($field->flags); // Lihat apakah ada flag aneh
            echo "</pre>";
            break;
        }
    }
    $stmt_meta->free();
} else {
    echo "<pre style='background-color:#ffeeff; padding:10px; border:1px solid #ffccff;'>";
    echo "DEBUG: Could not get result metadata for 'image' column.<br>";
    echo "</pre>";
}
// --- AKHIR DEBUG KOLOM TYPE ---

$stmt->close(); // Tutup prepared statement SELECT pertama

echo "<pre style='background-color:#ffeeee; padding:10px; border:1px solid #ffaaaa;'>";
echo "DEBUG: Data Produk Saat Dimuat (sebelum POST):<br>";
echo "Product Data: "; var_dump($product);
echo "</pre>";

if (!$product) {
  echo "Produk tidak ditemukan atau bukan milik Anda.";
  exit();
}

$error = '';
$success_message = ''; // Variabel untuk pesan sukses

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = $_POST['name'];
  $category = $_POST['category'];
  $description = $_POST['description'];
  $price = $_POST['price'];
  $stock = $_POST['stock'];
  $status = $_POST['status'];
  $variant = $_POST['variant'];

  // Inisialisasi $image_path_to_save dengan nilai gambar lama dari database
  // Ini adalah nilai default jika tidak ada gambar baru yang diunggah atau dihapus
  $image_path_to_save = $product['image'];

  echo "<pre style='background-color:#eeffee; padding:10px; border:1px solid #aaffee;'>";
  echo "DEBUG A: Nilai awal image_path_to_save (dari DB): ";
  var_dump($image_path_to_save);
  echo "DEBUG A: Type of image_path_to_save: "; var_dump(gettype($image_path_to_save));
  echo "</pre>";

  // --- LOGIKA UNTUK HAPUS GAMBAR LAMA VIA TOMBOL ---
  // Ini memiliki prioritas tertinggi
  if (isset($_POST['delete_current_image']) && $_POST['delete_current_image'] == '1') {
      echo "<pre style='background-color:#fff0e0; padding:10px; border:1px solid #ffcc99;'>";
      echo "DEBUG: Tombol Hapus Gambar Ditekan.<br>";
      echo "</pre>";

      $old_image_path_in_db = $product['image'];
      // Pastikan path fisik relatif dari lokasi edit_product.php
      $old_image_full_physical_path = '../' . $old_image_path_in_db;

      // Cek apakah ada path gambar lama dan file fisiknya ada
      if (!empty($old_image_path_in_db) && file_exists($old_image_full_physical_path) && !is_dir($old_image_full_physical_path)) {
          if (!unlink($old_image_full_physical_path)) {
              error_log("Gagal menghapus gambar lama (dari tombol): " . $old_image_full_physical_path . " - " . error_get_last()['message']);
              $error .= " Gagal menghapus gambar lama secara fisik. Mohon hapus manual jika tidak digunakan: " . htmlspecialchars($old_image_path_in_db);
              echo "<pre style='background-color:#ffeeee; padding:10px; border:1px solid #ffaaaa;'>";
              echo "DEBUG: GAGAL menghapus gambar lama (dari tombol)! Check server logs. Path: " . htmlspecialchars($old_image_full_physical_path) . "<br>";
              echo "DEBUG: Error details (unlink): "; var_dump(error_get_last());
              echo "</pre>";
          } else {
              echo "<pre style='background-color:#eeffef; padding:10px; border:1px solid #aaffaa;'>";
              echo "DEBUG: Berhasil menghapus gambar lama (dari tombol): " . htmlspecialchars($old_image_full_physical_path) . "<br>";
              echo "</pre>";
              $success_message = "Gambar produk berhasil dihapus.";
          }
      } else {
          echo "<pre style='background-color:#fff0e0; padding:10px; border:1px solid #ffcc99;'>";
          echo "DEBUG: Gambar lama tidak ditemukan atau path kosong/invalid, tidak ada yang dihapus fisik.<br>";
          echo "</pre>";
          if (!empty($old_image_path_in_db) && $old_image_path_in_db != '0') { // Jika ada path tapi file tidak ada, anggap sukses hapus di DB
              $success_message = "Path gambar produk berhasil dikosongkan di database.";
          }
      }
      // Set path gambar di database menjadi kosong karena telah dihapus/dikondisikan untuk dihapus
      $image_path_to_save = ''; // <--- Tetap string kosong untuk pengujian ini
  }
  // --- AKHIR LOGIKA HAPUS GAMBAR LAMA VIA TOMBOL ---


  // --- LOGIKA PENANGANAN GAMBAR BARU (JIKA DIUNGGAH) ATAU MEMPERTAHANKAN YANG LAMA ---
  // Logika ini HANYA berjalan jika tombol hapus gambar TIDAK ditekan
  if (!isset($_POST['delete_current_image']) || $_POST['delete_current_image'] != '1') {
      // Periksa apakah ada file gambar baru yang diunggah DAN tidak ada error upload (UPLOAD_ERR_OK)
      if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // --- DEBUG POINT B: File baru valid dan siap diunggah ---
        echo "<pre style='background-color:#e0ffe0; padding:10px; border:1px solid #a0ffa0;'>";
        echo "DEBUG B: File gambar baru valid dan siap diunggah.<br>";
        var_dump("File info (\$_FILES['image']):", $_FILES['image']);
        echo "</pre>";

        $target_dir = "../assets/assets/image/";
        // Buat direktori target jika belum ada
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $image_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $image_name = uniqid() . "." . $image_extension; // Nama unik untuk gambar baru
        $target_file = $target_dir . $image_name;
        $uploadOk = 1; // Flag untuk proses validasi tambahan

        // Validasi tambahan (getimagesize, ukuran file, ekstensi file)
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
          $error = "File bukan gambar.";
          $uploadOk = 0;
        }
        if ($_FILES["image"]["size"] > 5000000) { // 5MB
          $error = "Ukuran file terlalu besar (maks 5MB).";
          $uploadOk = 0;
        }
        if(!in_array($image_extension, ["jpg", "png", "jpeg", "gif"])) {
          $error = "Hanya JPG, JPEG, PNG & GIF yang diizinkan.";
          $uploadOk = 0;
        }

        if ($uploadOk == 0) {
          $error = "Maaf, file Anda tidak terunggah." . ($error ? " " . $error : "");
          // Jika validasi upload gagal, path gambar tetap yang lama
          $image_path_to_save = $product['image'];
          echo "<pre style='background-color:#ffeeee; padding:10px; border:1px solid #ffaaaa;'>";
          echo "DEBUG C1: Validasi upload GAGAL. image_path_to_save tetap: ";
          var_dump($image_path_to_save);
          echo "</pre>";
        } else {
          // Hapus gambar lama HANYA JIKA ada gambar baru yang valid diunggah
          $old_image_path_in_db = $product['image'];
          $old_image_full_physical_path = '../' . $old_image_path_in_db;

          if (!empty($old_image_path_in_db) && file_exists($old_image_full_physical_path) && !is_dir($old_image_full_physical_path)) {
              // Cek untuk memastikan gambar lama bukan gambar yang sama persis dengan yang baru diunggah
              // (Meskipun uniqid() sangat mengurangi kemungkinan ini)
              if (basename($old_image_full_physical_path) !== $image_name) {
                  if (!unlink($old_image_full_physical_path)) {
                      error_log("Gagal menghapus gambar lama: " . $old_image_full_physical_path . " - " . error_get_last()['message']);
                      $error .= " Gagal menghapus gambar lama. Mohon hapus manual jika tidak digunakan: " . htmlspecialchars($old_image_path_in_db);
                  } else {
                      echo "<pre style='background-color:#eeffef; padding:10px; border:1px solid #aaffaa;'>";
                      echo "DEBUG C2: Berhasil menghapus gambar lama: " . htmlspecialchars($old_image_full_physical_path) . "<br>";
                      echo "</pre>";
                  }
              }
          }

          // Pindahkan file yang diunggah dari direktori sementara ke target
          if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path_to_save = "assets/assets/image/" . $image_name; // Set path baru yang akan disimpan di database
            echo "<pre style='background-color:#eefeff; padding:10px; border:1px solid #aaffff;'>";
            echo "DEBUG C3: move_uploaded_file BERHASIL! image_path_to_save sekarang: ";
            var_dump($image_path_to_save);
            echo "</pre>";
          } else {
            $error = "Maaf, terjadi kesalahan saat mengunggah file Anda.";
            // Jika move_uploaded_file gagal, pastikan image_path_to_save tetap yang lama
            $image_path_to_save = $product['image'];
            echo "<pre style='background-color:#ffeeee; padding:10px; border:1px solid #ffaaaa;'>";
            echo "DEBUG C4: move_uploaded_file GAGAL! image_path_to_save kembali ke: ";
            var_dump($image_path_to_save);
            echo "</pre>";
          }
        }
      } else if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
          // --- DEBUG POINT D: Ada error upload selain OK dan NO_FILE (misal UPLOAD_ERR_INI_SIZE, dll.) ---
          $error_code = $_FILES['image']['error'];
          $error_message = '';
          switch ($error_code) {
              case UPLOAD_ERR_INI_SIZE: $error_message = "File yang diunggah melebihi batas ukuran maksimal di php.ini."; break;
              case UPLOAD_ERR_FORM_SIZE: $error_message = "File yang diunggah melebihi batas ukuran maksimal yang ditentukan di form."; break;
              case UPLOAD_ERR_PARTIAL: $error_message = "File hanya sebagian terunggah."; break;
              case UPLOAD_ERR_CANT_WRITE: $error_message = "Gagal menulis file ke disk. Periksa izin folder."; break;
              case UPLOAD_ERR_NO_TMP_DIR: $error_message = "Folder sementara tidak ditemukan."; break;
              case UPLOAD_ERR_EXTENSION: $error_message = "Ekstensi PHP menghentikan unggahan file."; break;
              default: $error_message = "Terjadi masalah tidak diketahui saat mengunggah file. Kode: " . $error_code; break;
          }
          $error = "Gagal mengunggah file: " . $error_message;
          // Penting: Jika ada error upload, path gambar tetap yang lama
          $image_path_to_save = $product['image'];
          echo "<pre style='background-color:#ffdddd; padding:10px; border:1px solid #ffaaaa;'>";
          echo "DEBUG D: Error upload file PHP. image_path_to_save tetap: ";
          var_dump($image_path_to_save);
          echo "</pre>";
      }
      // KASUS PENTING: Jika $_FILES['image']['error'] adalah UPLOAD_ERR_NO_FILE (pengguna tidak memilih file baru)
      // atau jika $_FILES['image'] bahkan tidak diset (misal jika input file tidak ada dalam form, yang tidak mungkin di sini),
      // maka tidak ada blok if/else if di atas yang akan terpanggil.
      // Dalam skenario ini, $image_path_to_save akan tetap pada nilai awalnya dari $product['image'].
      // Ini adalah perilaku yang diinginkan untuk "simpan tanpa mengubah gambar".
  }
  // --- AKHIR LOGIKA PENANGANAN GAMBAR BARU ATAU MEMPERTAHANKAN YANG LAMA ---

  // --- DEBUG POINT E: Nilai AKHIR image_path_to_save sebelum query UPDATE ---
  echo "<pre style='background-color:#ffffee; padding:10px; border:1px solid #ffffaa;'>";
  echo "DEBUG E: Nilai AKHIR image_path_to_save sebelum query UPDATE: ";
  var_dump($image_path_to_save);
  echo "DEBUG E: Type of image_path_to_save: "; var_dump(gettype($image_path_to_save));
  echo "</pre>";

  // --- DEBUG POINT F: Tampilkan semua nilai yang akan di-binding ---
  echo "<pre style='background-color:#ccffff; padding:10px; border:1px solid #99ccff;'>";
  echo "DEBUG F: Nilai yang akan dibinding ke UPDATE query:<br>";
  echo "name: "; var_dump($name);
  echo "category: "; var_dump($category);
  echo "description: "; var_dump($description);
  echo "price: "; var_dump($price);
  echo "image: "; var_dump($image_path_to_save); // Sangat penting untuk diperiksa di sini
  echo "stock: "; var_dump($stock);
  echo "status: "; var_dump($status);
  echo "variant: "; var_dump($variant);
  echo "product_id: "; var_dump($product_id);
  echo "seller_id: "; var_dump($seller_id);
  echo "</pre>";

  // --- DEBUG POINT G: Full SQL Query (HANYA UNTUK DEBUGGING!) ---
  // Ini membantu memverifikasi apa yang akan dikirim MySQL.
  // JANGAN GUNAKAN INI DI LINGKUNGAN PRODUKSI KARENA RENTAN SQL INJECTION
  $debug_sql = "UPDATE products SET name='" . $conn->real_escape_string($name) . "', category='" . $conn->real_escape_string($category) . "', description='" . $conn->real_escape_string($description) . "', price=" . $price . ", image='" . $conn->real_escape_string($image_path_to_save) . "', stock=" . $stock . ", status='" . $conn->real_escape_string($status) . "', variant='" . $conn->real_escape_string($variant) . "' WHERE id=" . $product_id . " AND seller_id=" . $seller_id;

  echo "<pre style='background-color:#ffeacc; padding:10px; border:1px solid #ffaa66;'>";
  echo "DEBUG G: Full SQL Query (for debugging only!):<br>";
  echo htmlspecialchars($debug_sql);
  echo "</pre>";
  // --- AKHIR DEBUG POINT G ---


  // Persiapkan dan eksekusi query UPDATE menggunakan Prepared Statement (SANGAT DIREKOMENDASIKAN)
  $stmt = $conn->prepare("UPDATE products SET name=?, category=?, description=?, price=?, image=?, stock=?, status=?, variant=? WHERE id=? AND seller_id=?");
  // Perhatikan urutan dan tipe di bind_param: name (s), category (s), description (s), price (d),
  // image (s), stock (i), status (s), variant (s), product_id (i), seller_id (i)
  $stmt->bind_param("ssdsisssii", $name, $category, $description, $price, $image_path_to_save, $stock, $status, $variant, $product_id, $seller_id);


  if ($stmt->execute()) {
    $success_message .= (empty($success_message) ? "" : " ") . "Produk berhasil diperbarui!";
    echo "<pre style='background-color:#e0ffe0; padding:10px; border:1px solid #a0ffa0;'>";
    echo "DEBUG: Produk berhasil diperbarui! Seharusnya redirect sekarang.";
    echo "</pre>";

    // --- DEBUG POINT H: Ambil ulang data 'image' dari database setelah update berhasil ---
    // Ini adalah pengecekan paling langsung untuk melihat apa yang sebenarnya tersimpan
    $stmt_check = $conn->prepare("SELECT image FROM products WHERE id = ? AND seller_id = ?");
    $stmt_check->bind_param("ii", $product_id, $seller_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $updated_product_data = $result_check->fetch_assoc();
    $stmt_check->close();

    echo "<pre style='background-color:#cceeff; padding:10px; border:1px solid #99ccff;'>";
    echo "DEBUG H: Nilai 'image' di database setelah update (Penting!):";
    var_dump($updated_product_data['image']);
    echo "DEBUG H: Type of 'image' di database setelah update:";
    var_dump(gettype($updated_product_data['image']));
    echo "</pre>";
    // --- AKHIR DEBUG POINT H ---

    // header("Location: sellerproducts.php"); // Aktifkan ini setelah debugging selesai
    // exit();
  } else {
    $error = "Gagal memperbarui produk: " . $stmt->error;
    echo "<pre style='background-color:#ffeedd; padding:10px; border:1px solid #ffaa99;'>";
    echo "DEBUG: Update database GAGAL! Error: " . htmlspecialchars($stmt->error);
    echo "</pre>";
  }
  $stmt->close(); // Tutup prepared statement UPDATE
} // End of if POST method


// Ambil data produk lagi (setelah POST, atau saat GET request awal)
// Ini penting agar form menampilkan data terbaru jika tidak ada redirect
$stmt = $conn->prepare("SELECT id, name, category, description, price, image, stock, status, variant FROM products WHERE id = ? AND seller_id = ?");
$stmt->bind_param("ii", $product_id, $seller_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

$conn->close(); // Tutup koneksi database utama
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Produk</title>
  <link rel="stylesheet" href="../assets/assets/seller.css">
  <link rel="stylesheet" href="../assets/assets/editproduct.css">
  <style>
    /* Umum untuk container dan judul */
    .edit-container {
        max-width: 700px;
        margin: 40px auto;
        padding: 30px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    h2 {
        text-align: center;
        color: #e91e63;
        margin-bottom: 30px;
        font-size: 2em;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.05);
    }

    /* Styling untuk form group (label, input, textarea, select) */
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #555;
    }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea,
    .form-group select {
        width: calc(100% - 22px); /* Penyesuaian untuk padding */
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1em;
        box-sizing: border-box; /* Penting agar padding tidak menambah lebar */
        transition: border-color 0.3s ease;
    }
    .form-group input[type="text"]:focus,
    .form-group input[type="number"]:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 5px rgba(0,123,255,0.2);
    }
    textarea {
        resize: vertical; /* Memungkinkan textarea diubah ukurannya secara vertikal */
        min-height: 100px;
    }
    .form-group input[type="file"] {
        padding: 10px 0; /* Padding vertikal untuk input file */
    }
    small {
        color: #888;
        font-size: 0.85em;
        margin-top: 5px;
        display: block; /* Agar small ada di baris baru */
    }

    /* Styling untuk kontrol gambar (gambar saat ini dan tombol hapus) */
    .image-controls {
        display: flex; /* Menggunakan flexbox untuk tata letak horizontal */
        flex-direction: row; /* Atur item dalam satu baris (default) */
        align-items: flex-start; /* Mengatur alignment item ke atas */
        gap: 20px; /* Jarak antara gambar dan tombol */
        margin-top: 10px;
        margin-bottom: 15px; /* Jarak ke input file */
        flex-wrap: wrap; /* Memungkinkan wrap ke baris baru pada layar kecil */
        border: 1px solid #eee; /* Tambahkan border untuk visualisasi area kontrol */
        padding: 15px;
        border-radius: 8px;
        background-color: #f9f9f9;
    }
    .current-image {
        max-width: 200px; /* Lebar maksimum gambar */
        height: auto; /* Tinggi otomatis agar aspek rasio terjaga */
        object-fit: contain; /* Memastikan seluruh gambar terlihat dalam batas tanpa cropping */
        display: block;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        flex-shrink: 0; /* Mencegah gambar menyusut jika ruang terbatas */
    }
    .image-controls button {
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.9em;
        transition: background-color 0.3s ease;
        cursor: pointer;
        border: none;
        align-self: center;
    }
    .btn-delete-image {
        background-color: #dc3545;
        color: white;
    }
    .btn-delete-image:hover {
        background-color: #c82333;
    }

    /* Styling untuk tombol submit */
    button[type="submit"] {
        display: block;
        width: 100%;
        padding: 12px 20px;
        background-color: #e91e63;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 1.1em;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    button[type="submit"]:hover {
        background-color: #c2185b;
        transform: translateY(-2px);
    }

    /* Styling untuk pesan error/sukses */
    .error-msg, .success-msg {
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 6px;
        text-align: center;
        font-weight: bold;
    }
    .error-msg {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .success-msg {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
  </style>
</head>
<body>
  <div class="edit-container">
    <h2>✏️ Edit Produk</h2>

    <?php if (isset($error) && !empty($error)) : ?>
      <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (isset($success_message) && !empty($success_message)) : ?>
      <p class="success-msg"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>

    <form method="POST" class="edit-form" enctype="multipart/form-data">
      <div class="form-group">
        <label for="name">Nama Produk</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
      </div>

      <div class="form-group">
        <label for="category">Kategori</label>
        <input type="text" id="category" name="category" value="<?= htmlspecialchars($product['category']) ?>" required>
      </div>

      <div class="form-group">
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" required><?= htmlspecialchars($product['description']) ?></textarea>
      </div>

      <div class="form-group">
        <label for="price">Harga</label>
        <input type="number" step="0.01" id="price" name="price" value="<?= $product['price'] ?>" required>
      </div>

      <div class="form-group">
        <label for="image">Gambar Produk</label>
        <?php if (!empty($product['image']) && $product['image'] != '0'): ?>
            <p>Gambar Saat Ini:</p>
            <div class="image-controls">
                <img src="../<?= htmlspecialchars($product['image']) ?>" alt="Gambar Produk Saat Ini" class="current-image">
                <button type="submit" name="delete_current_image" value="1" class="btn-delete-image" onclick="return confirm('Apakah Anda yakin ingin menghapus gambar ini? Ini tidak bisa dibatalkan.')">🗑️ Hapus Gambar</button>
            </div>
        <?php else: ?>
            <p>Tidak ada gambar saat ini.</p>
        <?php endif; ?>
        <input type="file" id="image" name="image" accept="image/*">
        <small>Biarkan kosong jika tidak ingin mengubah gambar.</small>
      </div>

      <div class="form-group">
        <label for="stock">Stok</label>
        <input type="number" id="stock" name="stock" value="<?= $product['stock'] ?>" required min="0">
      </div>

      <div class="form-group">
        <label for="status">Status Produk</label>
        <select id="status" name="status" required>
            <option value="Normal" <?= ($product['status'] == 'Normal') ? 'selected' : ''; ?>>Normal</option>
            <option value="New Arrival" <?= ($product['status'] == 'New Arrival') ? 'selected' : ''; ?>>New Arrival</option>
            <option value="Hot" <?= ($product['status'] == 'Hot') ? 'selected' : ''; ?>>Hot</option>
        </select>
      </div>

      <div class="form-group">
        <label for="variant">Varian (jika ada)</label>
        <input type="text" id="variant" name="variant" value="<?= htmlspecialchars($product['variant']) ?>">
        <small>Contoh: Warna Merah, Ukuran S; atau kosongkan jika tidak ada.</small>
      </div>

      <button type="submit">Simpan Perubahan</button>
    </form>
  </div>
</body>
</html>