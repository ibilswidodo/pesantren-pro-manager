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
