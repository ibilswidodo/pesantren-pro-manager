<?php
/**
 * Plugin Name: Pesantren Pro Manager
 * Description: Sistem Manajemen Pondok Pesantren Modern (Tahap 1: Data Santri & UI Foundation)
 * Version: 1.0
 * Author: Alifbata Digital
 */

if (!defined('ABSPATH')) exit;

// 1. Inisialisasi Plugin & Tailwind CSS untuk Admin
add_action('admin_enqueue_scripts', 'ppm_enqueue_admin_assets');
function ppm_enqueue_admin_assets($hook) {
    // Hanya load di halaman plugin kita agar tidak bentrok dengan plugin lain
    if (strpos($hook, 'santri') !== false || strpos($hook, 'pesantren') !== false) {
        wp_enqueue_script('tailwind-cdn', 'https://cdn.tailwindcss.com');
        wp_enqueue_style('google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
    }
}

// 2. Registrasi Custom Post Type: Santri
add_action('init', 'ppm_register_santri_cpt');
function ppm_register_santri_cpt() {
    $labels = [
        'name' => 'Santri',
        'singular_name' => 'Santri',
        'menu_name' => 'Pesantren Pro',
        'add_new' => 'Tambah Santri Baru',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-mortarboard', // Icon Topi Toga
        'supports' => ['title'], // Kita pakai meta box untuk field lengkapnya
        'show_in_rest' => true,
    ];
    register_post_type('santri', $args);
}

// 3. Menambahkan Meta Box (Form Input Detail Santri)
add_action('add_meta_boxes', 'ppm_santri_meta_boxes');
function ppm_santri_meta_boxes() {
    add_meta_box('ppm_detail_santri', 'Data Lengkap Santri', 'ppm_callback_santri_details', 'santri', 'normal', 'high');
}

function ppm_callback_santri_details($post) {
    $nis = get_post_meta($post->ID, '_ppm_nis', true);
    $wa_wali = get_post_meta($post->ID, '_ppm_wa_wali', true);
    $kamar = get_post_meta($post->ID, '_ppm_kamar', true);
    $kelas = get_post_meta($post->ID, '_ppm_kelas', true);
    
    // Nonce untuk keamanan
    wp_nonce_field('ppm_save_santri_meta', 'ppm_santri_nonce');

    // Tampilan Form dengan Tailwind
    echo '
    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6 font-["Inter"]">
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">Nomor Induk Santri (NIS)</label>
            <input type="text" name="ppm_nis" value="'.esc_attr($nis).'" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500" placeholder="Contoh: 20240001">
        </div>
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">WhatsApp Wali (Format: 628xxx)</label>
            <input type="text" name="ppm_wa_wali" value="'.esc_attr($wa_wali).'" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500" placeholder="628123456789">
        </div>
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">Nama Kamar / Asrama</label>
            <input type="text" name="ppm_kamar" value="'.esc_attr($kamar).'" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500">
        </div>
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">Jenjang Kelas</label>
            <select name="ppm_kelas" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500">
                <option value="Ula" '.selected($kelas, 'Ula', false).'>Ula (Dasar)</option>
                <option value="Wustho" '.selected($kelas, 'Wustho', false).'>Wustho (Menengah)</option>
                <option value="Ulya" '.selected($kelas, 'Ulya', false).'>Ulya (Atas)</option>
            </select>
        </div>
    </div>';
}

// 4. Menyimpan Data Meta Box
add_action('save_post', 'ppm_save_santri_details');
function ppm_save_santri_details($post_id) {
    if (!isset($_POST['ppm_santri_nonce']) || !wp_verify_nonce($_POST['ppm_santri_nonce'], 'ppm_save_santri_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $fields = ['ppm_nis', 'ppm_wa_wali', 'ppm_kamar', 'ppm_kelas'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}

// --- TAHAP 2: FITUR IMPORT CSV ---

// 1. Membuat Submenu Import di Menu Pesantren Pro
add_action('admin_menu', 'ppm_register_import_submenu');
function ppm_register_import_submenu() {
    add_submenu_page(
        'edit.php?post_type=santri',
        'Import Santri',
        'Import CSV',
        'manage_options',
        'ppm-import-santri',
        'ppm_import_santri_page'
    );
}

// 2. Fungsi Download Template CSV
add_action('admin_init', 'ppm_handle_csv_download');
function ppm_handle_csv_download() {
    if (isset($_GET['action']) && $_GET['action'] === 'ppm_download_template') {
        if (!current_user_can('manage_options')) return;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=template-santri.csv');
        
        $output = fopen('php://output', 'w');
        // Header CSV
        fputcsv($output, ['nama_lengkap', 'nis', 'wa_wali', 'kamar', 'kelas']);
        // Contoh Data
        fputcsv($output, ['Ahmad Zaki', '2024001', '628123456789', 'Gedung A-01', 'Wustho']);
        
        fclose($output);
        exit;
    }
}

// 3. Halaman Dashboard Import (UI Modern dengan Tailwind)
function ppm_import_santri_page() {
    ?>
    <div class="wrap mt-10 mr-5 font-['Inter']">
        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200">
            <h1 class="text-2xl font-bold text-emerald-700 mb-2">Import Data Santri</h1>
            <p class="text-gray-600 mb-6">Unggah file CSV untuk memasukkan data santri secara massal ke sistem.</p>

            <?php if (isset($_GET['imported'])) : ?>
                <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-700 p-4 mb-6">
                    Berhasil mengimport <?php echo intval($_GET['imported']); ?> data santri.
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="ppm_import_csv_logic">
                        <?php wp_nonce_field('ppm_import_nonce', 'ppm_nonce'); ?>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File CSV</label>
                            <input type="file" name="csv_file" accept=".csv" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        </div>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                            Mulai Import Data
                        </button>
                    </form>
                </div>

                <div class="bg-gray-50 p-6 rounded-lg">
                    <h3 class="font-bold text-gray-800 mb-2 text-lg">Instruksi Import:</h3>
                    <ul class="list-disc list-inside text-gray-600 text-sm space-y-2">
                        <li>Gunakan format file <strong>.CSV</strong></li>
                        <li>Pastikan kolom sesuai dengan template (Nama, NIS, WA Wali, dll)</li>
                        <li>NIS tidak boleh duplikat dengan data yang sudah ada.</li>
                        <li>Format WA harus dimulai dengan kode negara (Contoh: 62812...)</li>
                    </ul>
                    <a href="?post_type=santri&page=ppm-import-santri&action=ppm_download_template" class="inline-block mt-4 text-emerald-600 font-semibold hover:underline">
                        &darr; Download Template CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// 4. Logika Pemrosesan Data CSV
add_action('admin_post_ppm_import_csv_logic', 'ppm_process_csv_import');
function ppm_process_csv_import() {
    if (!isset($_POST['ppm_nonce']) || !wp_verify_nonce($_POST['ppm_nonce'], 'ppm_import_nonce')) die('Security check failed');

    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file); // Skip header row
        
        $count = 0;
        while (($column = fgetcsv($file)) !== FALSE) {
            $nama  = sanitize_text_field($column[0]);
            $nis   = sanitize_text_field($column[1]);
            $wa    = sanitize_text_field($column[2]);
            $kamar = sanitize_text_field($column[3]);
            $kelas = sanitize_text_field($column[4]);

            // Cek duplikasi NIS
            $existing = get_posts([
                'post_type'  => 'santri',
                'meta_key'   => '_ppm_nis',
                'meta_value' => $nis
            ]);

            if (empty($existing) && !empty($nama)) {
                $post_id = wp_insert_post([
                    'post_title'   => $nama,
                    'post_type'    => 'santri',
                    'post_status'  => 'publish'
                ]);

                if ($post_id) {
                    update_post_meta($post_id, '_ppm_nis', $nis);
                    update_post_meta($post_id, '_ppm_wa_wali', $wa);
                    update_post_meta($post_id, '_ppm_kamar', $kamar);
                    update_post_meta($post_id, '_ppm_kelas', $kelas);
                    $count++;
                }
            }
        }
        fclose($file);
        wp_redirect(admin_url('edit.php?post_type=santri&page=ppm-import-santri&imported=' . $count));
        exit;
    }
}

// --- TAHAP 3: MANAJEMEN KEUANGAN (SPP & TAGIHAN) ---

// 1. Membuat Tabel Database Kustom saat Plugin Aktif
register_activation_hook(__FILE__, 'ppm_create_finance_table');
function ppm_create_finance_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ppm_keuangan';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        santri_id bigint(20) NOT NULL,
        jenis_tagihan varchar(100) NOT NULL,
        jumlah decimal(10,2) NOT NULL,
        status varchar(20) DEFAULT 'Belum Lunas',
        tanggal_tagihan date NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// 2. Menambahkan Submenu Keuangan
add_action('admin_menu', 'ppm_register_finance_submenu');
function ppm_register_finance_submenu() {
    add_submenu_page(
        'edit.php?post_type=santri',
        'Keuangan Santri',
        'Keuangan',
        'manage_options',
        'ppm-keuangan',
        'ppm_finance_page'
    );
}

// 3. Halaman Dashboard Keuangan
function ppm_finance_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ppm_keuangan';
    
    // Logika Generate Tagihan Massal (Contoh sederhana)
    if (isset($_POST['generate_spp'])) {
        $santris = get_posts(['post_type' => 'santri', 'numberposts' => -1]);
        $bulan_ini = date('Y-m-01');
        foreach ($santris as $s) {
            $wpdb->insert($table_name, [
                'santri_id' => $s->ID,
                'jenis_tagihan' => 'SPP Bulan ' . date('F Y'),
                'jumlah' => 500000, // Misal nominal SPP 500rb
                'status' => 'Belum Lunas',
                'tanggal_tagihan' => $bulan_ini
            ]);
        }
        echo '<div class="updated"><p>Tagihan SPP bulan ini berhasil dibuat untuk semua santri.</p></div>';
    }

    // Ambil data transaksi
    $transaksi = $wpdb->get_results("SELECT t.*, p.post_title as nama_santri 
                                     FROM $table_name t 
                                     JOIN {$wpdb->posts} p ON t.santri_id = p.ID 
                                     ORDER BY t.id DESC LIMIT 20");

    ?>
    <div class="wrap mt-10 mr-5 font-['Inter']">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-emerald-500">
                <h3 class="text-gray-500 text-sm font-medium">Total Tagihan</h3>
                <p class="text-2xl font-bold text-gray-800">Rp <?php echo number_format(50000000); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-orange-500">
                <h3 class="text-gray-500 text-sm font-medium">Belum Bayar</h3>
                <p class="text-2xl font-bold text-gray-800">Rp <?php echo number_format(12500000); ?></p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                <form method="post">
                    <button type="submit" name="generate_spp" class="w-full bg-emerald-600 text-white py-2 px-4 rounded-lg hover:bg-emerald-700 transition">
                        Buat Tagihan SPP Massal
                    </button>
                </form>
                <p class="text-xs text-gray-400 mt-2 text-center text-italic">*Klik untuk buat tagihan 500rb ke semua santri</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">Transaksi Terakhir</h2>
                <span class="bg-emerald-100 text-emerald-700 text-xs px-3 py-1 rounded-full font-semibold">Live Data</span>
            </div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-4 text-sm font-semibold text-gray-600">Nama Santri</th>
                        <th class="p-4 text-sm font-semibold text-gray-600">Jenis</th>
                        <th class="p-4 text-sm font-semibold text-gray-600">Jumlah</th>
                        <th class="p-4 text-sm font-semibold text-gray-600">Status</th>
                        <th class="p-4 text-sm font-semibold text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transaksi as $t) : ?>
                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                        <td class="p-4 text-sm text-gray-800 font-medium"><?php echo $t->nama_santri; ?></td>
                        <td class="p-4 text-sm text-gray-600"><?php echo $t->jenis_tagihan; ?></td>
                        <td class="p-4 text-sm text-gray-800 font-bold">Rp <?php echo number_format($t->jumlah); ?></td>
                        <td class="p-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs font-bold <?php echo $t->status == 'Lunas' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php echo $t->status; ?>
                            </span>
                        </td>
                        <td class="p-4 text-sm">
                            <button class="text-emerald-600 hover:text-emerald-800 font-semibold underline">Update Status</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// --- TAHAP 4: MODUL PENDAFTARAN (PPDB) ---

// 1. Registrasi CPT Pendaftaran (Internal)
add_action('init', 'ppm_register_ppdb_cpt');
function ppm_register_ppdb_cpt() {
    register_post_type('pendaftaran', [
        'labels' => ['name' => 'Pendaftaran Baru', 'singular_name' => 'Pendaftar'],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-id-alt',
        'supports' => ['title'],
        'show_in_menu' => 'edit.php?post_type=santri', // Gabung di bawah menu Pesantren Pro
    ]);
}

// 2. Shortcode Form Pendaftaran [form_ppdb]
add_shortcode('form_ppdb', 'ppm_ppdb_form_render');
function ppm_ppdb_form_render() {
    ob_start();
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <div class="max-w-2xl mx-auto my-10 font-['Inter'] bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-100">
        <div class="bg-emerald-600 p-6 text-white text-center">
            <h2 class="text-2xl font-bold">Penerimaan Santri Baru</h2>
            <p class="text-emerald-100 italic">Silakan lengkapi formulir di bawah ini</p>
        </div>
        
        <form action="" method="post" class="p-8 space-y-5">
            <?php wp_nonce_field('ppm_ppdb_submit', 'ppdb_nonce'); ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap Santri</label>
                    <input type="text" name="nama_calon" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">NIK (Sesuai KK)</label>
                    <input type="text" name="nik_calon" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Wali / Orang Tua</label>
                <input type="text" name="wali_calon" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">No. WhatsApp Aktif (Untuk Informasi)</label>
                <input type="tel" name="wa_calon" placeholder="628..." required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>

            <button type="submit" name="submit_ppdb" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-lg transition-all shadow-lg transform hover:-translate-y-1">
                Kirim Pendaftaran
            </button>
        </form>
    </div>
    <?php
    
    // Logika Simpan Data Pendaftaran
    if (isset($_POST['submit_ppdb']) && wp_verify_nonce($_POST['ppdb_nonce'], 'ppm_ppdb_submit')) {
        $nama = sanitize_text_field($_POST['nama_calon']);
        $new_id = wp_insert_post([
            'post_title' => $nama,
            'post_type' => 'pendaftaran',
            'post_status' => 'publish'
        ]);

        if ($new_id) {
            update_post_meta($new_id, '_ppm_ppdb_nik', sanitize_text_field($_POST['nik_calon']));
            update_post_meta($new_id, '_ppm_ppdb_wali', sanitize_text_field($_POST['wali_calon']));
            update_post_meta($new_id, '_ppm_ppdb_wa', sanitize_text_field($_POST['wa_calon']));
            echo '<div class="p-4 bg-green-100 text-green-800 rounded-lg max-w-2xl mx-auto text-center font-bold">Pendaftaran Berhasil Terkirim! Mohon tunggu konfirmasi admin.</div>';
        }
    }

    return ob_get_clean();
}

// 3. Menambahkan Meta Box di Halaman Admin Pendaftaran
add_action('add_meta_boxes', 'ppm_ppdb_meta_boxes');
function ppm_ppdb_meta_boxes() {
    add_meta_box('ppm_ppdb_detail', 'Data Pendaftar Baru', 'ppm_callback_ppdb_details', 'pendaftaran', 'normal', 'high');
}

function ppm_callback_ppdb_details($post) {
    $nik = get_post_meta($post->ID, '_ppm_ppdb_nik', true);
    $wali = get_post_meta($post->ID, '_ppm_ppdb_wali', true);
    $wa = get_post_meta($post->ID, '_ppm_ppdb_wa', true);
    
    echo "
    <div class='p-4 bg-slate-50 border rounded-lg space-y-2'>
        <p><strong>NIK:</strong> $nik</p>
        <p><strong>Nama Wali:</strong> $wali</p>
        <p><strong>WhatsApp:</strong> <a href='https://wa.me/$wa' target='_blank' class='text-emerald-600 underline font-bold'>Chat Sekarang ($wa)</a></p>
        <hr class='my-4'>
        <p class='text-sm text-gray-500'>Jika disetujui, admin bisa menyalin data ini secara manual ke menu Santri Aktif.</p>
    </div>";
}

// --- TAHAP 5: MODUL KEPENGURUSAN (HRM) & WHATSAPP LINK ---

// 1. Registrasi CPT Pengurus
add_action('init', 'ppm_register_pengurus_cpt');
function ppm_register_pengurus_cpt() {
    register_post_type('pengurus', [
        'labels' => [
            'name' => 'Pengurus & Asatidz',
            'singular_name' => 'Pengurus',
            'add_new' => 'Tambah Pengurus Baru'
        ],
        'public' => true,
        'show_ui' => true,
        'menu_icon' => 'dashicons-businessman',
        'supports' => ['title', 'thumbnail'], // Thumbnail untuk foto ustadz
        'show_in_menu' => 'edit.php?post_type=santri', // Gabung di menu utama
    ]);
}

// 2. Meta Box Detail Pengurus
add_action('add_meta_boxes', 'ppm_pengurus_meta_boxes');
function ppm_pengurus_meta_boxes() {
    add_meta_box('ppm_detail_pengurus', 'Informasi Pengurus', 'ppm_callback_pengurus_details', 'pengurus', 'normal', 'high');
}

function ppm_callback_pengurus_details($post) {
    $jabatan = get_post_meta($post->ID, '_ppm_jabatan', true);
    $wa_pengurus = get_post_meta($post->ID, '_ppm_wa_pengurus', true);
    $spesialisasi = get_post_meta($post->ID, '_ppm_spesialisasi', true);
    
    wp_nonce_field('ppm_save_pengurus_meta', 'ppm_pengurus_nonce');

    echo '
    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6 font-["Inter"]">
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">Jabatan / Amanah</label>
            <input type="text" name="ppm_jabatan" value="'.esc_attr($jabatan).'" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500" placeholder="Contoh: Lurah Pondok / Bendahara">
        </div>
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">No. WhatsApp</label>
            <input type="text" name="ppm_wa_pengurus" value="'.esc_attr($wa_pengurus).'" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500" placeholder="628xxx">
        </div>
        <div class="flex flex-col gap-2 md:col-span-2">
            <label class="font-semibold text-gray-700">Spesialisasi / Keahlian (Kitab/Materi)</label>
            <textarea name="ppm_spesialisasi" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500">'.esc_textarea($spesialisasi).'</textarea>
        </div>
    </div>';
}

// 3. Simpan Data Pengurus
add_action('save_post', 'ppm_save_pengurus_details');
function ppm_save_pengurus_details($post_id) {
    if (!isset($_POST['ppm_pengurus_nonce']) || !wp_verify_nonce($_POST['ppm_pengurus_nonce'], 'ppm_save_pengurus_meta')) return;
    
    $fields = ['ppm_jabatan', 'ppm_wa_pengurus', 'ppm_spesialisasi'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}

// 4. Integrasi Dashboard Quick Action (WhatsApp Tool)
add_action('wp_dashboard_setup', 'ppm_add_dashboard_widgets');
function ppm_add_dashboard_widgets() {
    wp_add_dashboard_widget('ppm_wa_quick_action', '⚡ Pesantren Quick Action', 'ppm_wa_widget_render');
}

function ppm_wa_widget_render() {
    echo '
    <div class="p-2 font-["Inter"]">
        <p class="text-sm text-gray-600 mb-4">Gunakan pintasan ini untuk mengirim pesan cepat ke wali murid.</p>
        <div class="space-y-3">
            <a href="https://wa.me/?text=' . urlencode('Assalamu’alaikum Bapak/Ibu, ini dari pengurus pondok...') . '" target="_blank" class="block text-center bg-emerald-500 text-white p-2 rounded hover:bg-emerald-600 transition no-underline">
                Kirim Pengumuman Umum (WA)
            </a>
            <a href="'.admin_url('edit.php?post_type=santri&page=ppm-keuangan').'" class="block text-center bg-blue-500 text-white p-2 rounded hover:bg-blue-600 transition no-underline">
                Cek Tunggakan SPP
            </a>
        </div>
    </div>';
}

// 5. Memberikan Styling pada Kolom Admin CPT agar Modern
add_filter('manage_pengurus_posts_columns', 'ppm_set_pengurus_columns');
function ppm_set_pengurus_columns($columns) {
    $columns['jabatan'] = 'Jabatan';
    $columns['wa'] = 'WhatsApp';
    return $columns;
}

add_action('manage_pengurus_posts_custom_column', 'ppm_custom_pengurus_column', 10, 2);
function ppm_custom_pengurus_column($column, $post_id) {
    switch ($column) {
        case 'jabatan' :
            echo esc_html(get_post_meta($post_id, '_ppm_jabatan', true));
            break;
        case 'wa' :
            $wa = get_post_meta($post_id, '_ppm_wa_pengurus', true);
            echo '<a href="https://wa.me/'.$wa.'" class="text-emerald-600 font-bold" target="_blank">'.$wa.'</a>';
            break;
    }
}
