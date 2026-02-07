<?php
if (!defined('ABSPATH')) exit;

// 1. Registrasi Submenu Import
add_action('admin_menu', function() {
    add_submenu_page(
        'ppm-dashboard', 
        'Import Data', 
        'Import CSV', 
        'manage_options', 
        'ppm-import', 
        'ppm_render_import_page'
    );
});

// 2. Handler Download Template CSV
add_action('admin_init', function() {
    if (isset($_GET['action']) && $_GET['action'] === 'ppm_download_csv') {
        if (!current_user_can('manage_options')) return;

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=template-santri-pesantren.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Nama Santri', 'NIS', 'WA Wali', 'Kamar', 'Kelas']);
        fputcsv($output, ['Contoh Ahmad', '2024001', '62812345678', 'Zaid Bin Tsabit', 'Wustho']);
        fclose($output);
        exit;
    }
});

// 3. Tampilan Halaman Import
function ppm_render_import_page() {
    ?>
    <div class="wrap mt-5 font-['Inter']">
        <div class="max-w-4xl bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Import Data Santri (Massal)</h1>
            <p class="text-gray-500 mb-8 text-sm">Gunakan fitur ini untuk memasukkan data santri dalam jumlah banyak sekaligus menggunakan file CSV.</p>

            <?php 
            // Logic Proses Import saat File Diunggah
            if (isset($_POST['start_import'])) {
                // Verifikasi keamanan nonce
                if (isset($_POST['ppm_import_nonce_field']) && wp_verify_nonce($_POST['ppm_import_nonce_field'], 'ppm_import_action')) {
                    ppm_process_csv_upload();
                } else {
                    echo "<div class='p-4 mb-6 bg-red-100 text-red-800 rounded-lg'>Sesi kadaluarsa, silakan coba lagi.</div>";
                }
            }
            ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="p-6 border-2 border-dashed border-emerald-200 rounded-xl bg-emerald-50/30">
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('ppm_import_action', 'ppm_import_nonce_field'); ?>
                        <label class="block text-sm font-bold text-emerald-800 mb-4 text-center">Pilih File CSV Anda</label>
                        <input type="file" name="csv_file" accept=".csv" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 mb-6">
                        
                        <button type="submit" name="start_import" class="w-full bg-emerald-600 text-white font-bold py-3 rounded-xl hover:bg-emerald-700 shadow-lg transition-all active:scale-95">
                            🚀 Mulai Unggah Sekarang
                        </button>
                    </form>
                </div>

                <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <span class="bg-emerald-600 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full italic">i</span> 
                        Panduan Pengisian:
                    </h3>
                    <ul class="text-xs text-gray-600 space-y-3">
                        <li class="flex gap-2"><strong>1.</strong> Unduh template resmi agar struktur kolom tidak salah.</li>
                        <li class="flex gap-2"><strong>2.</strong> Gunakan format <strong>.csv</strong> (bukan .xlsx).</li>
                        <li class="flex gap-2"><strong>3.</strong> Jangan mengubah urutan kolom yang sudah ada di template.</li>
                        <li class="flex gap-2"><strong>4.</strong> Nomor WA Wali harus diawali kode negara (62).</li>
                    </ul>
                    <a href="?page=ppm-import&action=ppm_download_csv" class="mt-5 inline-block text-emerald-600 font-bold text-sm hover:underline">
                        📥 Download Template CSV
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// 4. Logic Pemrosesan CSV
function ppm_process_csv_upload() {
    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        fgetcsv($file); // Melewati baris pertama (header)

        $count = 0;
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $nama  = sanitize_text_field($data[0]);
            $nis   = sanitize_text_field($data[1]);
            $wa    = sanitize_text_field($data[2]);
            $kamar = sanitize_text_field($data[3]);
            $kelas = sanitize_text_field($data[4]);

            if ($nama) {
                $post_id = wp_insert_post([
                    'post_title'  => $nama,
                    'post_type'   => 'santri',
                    'post_status' => 'publish'
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
        echo "<div class='p-4 mb-6 bg-emerald-100 text-emerald-800 rounded-lg border border-emerald-200 font-bold'>Alhamdulillah! Berhasil mengimport $count santri baru.</div>";
    }
}
