<?php
if (!defined('ABSPATH')) exit;

// 1. Registrasi Menu & Submenu khusus Santri
add_action('admin_menu', function() {
    add_submenu_page('ppm-dashboard', 'Data Santri', 'Data Santri', 'manage_options', 'ppm-santri', 'ppm_render_santri_list');
    add_submenu_page('ppm-dashboard', 'Tambah Santri', 'Tambah Santri', 'manage_options', 'ppm-santri-add', 'ppm_render_santri_form');
});

// 2. Tampilan Daftar Santri (Table View)
function ppm_render_santri_list() {
    // Handle Delete Logic
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        check_admin_referer('ppm_delete_santri_' . $_GET['id']);
        wp_delete_post($_GET['id'], true);
        echo '<div class="updated p-3 mb-4 bg-red-100 text-red-700 rounded">Data santri berhasil dihapus.</div>';
    }

    $santris = get_posts(['post_type' => 'santri', 'numberposts' => -1]);
    ?>
    <div class="wrap mt-5 font-['Inter']">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Daftar Santri Aktif</h1>
            <a href="?page=ppm-santri-add" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition shadow-md font-bold"> + Tambah Santri </a>
        </div>

        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-gray-600 text-xs uppercase tracking-widest">
                        <th class="p-4 font-semibold">NIS</th>
                        <th class="p-4 font-semibold">Nama Lengkap</th>
                        <th class="p-4 font-semibold">Kamar</th>
                        <th class="p-4 font-semibold">Kelas</th>
                        <th class="p-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if($santris) : foreach($santris as $s) : 
                        $nis = get_post_meta($s->ID, '_ppm_nis', true);
                        $kamar = get_post_meta($s->ID, '_ppm_kamar', true);
                        $kelas = get_post_meta($s->ID, '_ppm_kelas', true);
                        $delete_url = wp_nonce_url("?page=ppm-santri&action=delete&id=" . $s->ID, 'ppm_delete_santri_' . $s->ID);
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-sm font-mono text-emerald-700"><?php echo esc_html($nis); ?></td>
                        <td class="p-4 font-bold text-gray-800"><?php echo esc_html($s->post_title); ?></td>
                        <td class="p-4 text-sm text-gray-600"><?php echo esc_html($kamar); ?></td>
                        <td class="p-4 text-sm text-gray-600"><?php echo esc_html($kelas); ?></td>
                        <td class="p-4 text-center text-xs space-x-3">
                            <a href="?page=ppm-santri-add&edit=<?php echo $s->ID; ?>" class="text-emerald-600 hover:underline font-bold">Edit</a>
                            <a href="<?php echo $delete_url; ?>" class="text-red-400 hover:text-red-600 transition" onclick="return confirm('Hapus data santri ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="p-10 text-center text-gray-400">Belum ada data santri. Silakan tambah manual atau via Import CSV.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// 3. Tampilan Form Tambah/Edit Santri (Custom UI)
function ppm_render_santri_form() {
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    
    // Logic Simpan Data
    if (isset($_POST['save_santri']) && check_admin_referer('ppm_save_santri_action', 'ppm_santri_nonce')) {
        $nama = sanitize_text_field($_POST['nama']);
        $post_data = [
            'post_title' => $nama,
            'post_type'  => 'santri',
            'post_status' => 'publish',
            'ID'         => $edit_id
        ];
        
        $id = $edit_id ? wp_update_post($post_data) : wp_insert_post($post_data);
        
        if ($id) {
            update_post_meta($id, '_ppm_nis', sanitize_text_field($_POST['nis']));
            update_post_meta($id, '_ppm_kamar', sanitize_text_field($_POST['kamar']));
            update_post_meta($id, '_ppm_kelas', sanitize_text_field($_POST['kelas']));
            echo '<div class="updated p-3 mb-4 bg-emerald-100 text-emerald-700 rounded border border-emerald-200 font-bold italic">Data berhasil diperbarui!</div>';
        }
    }

    $nama = $edit_id ? get_the_title($edit_id) : '';
    $nis = $edit_id ? get_post_meta($edit_id, '_ppm_nis', true) : '';
    $kamar = $edit_id ? get_post_meta($edit_id, '_ppm_kamar', true) : '';
    $kelas = $edit_id ? get_post_meta($edit_id, '_ppm_kelas', true) : '';

    ?>
    <div class="wrap mt-5 font-['Inter']">
        <div class="max-w-3xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <h2 class="text-xl font-bold mb-6 text-emerald-700"><?php echo $edit_id ? '✏️ Edit Data Santri' : '➕ Input Santri Baru'; ?></h2>
            
            <form method="post" action="" class="space-y-6">
                <?php wp_nonce_field('ppm_save_santri_action', 'ppm_santri_nonce'); ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase mb-2 tracking-widest">Nama Lengkap Santri</label>
                        <input type="text" name="nama" value="<?php echo esc_attr($nama); ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase mb-2 tracking-widest">Nomor Induk Santri (NIS)</label>
                        <input type="text" name="nis" value="<?php echo esc_attr($nis); ?>" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase mb-2 tracking-widest">Kamar / Asrama</label>
                        <input type="text" name="kamar" value="<?php echo esc_attr($kamar); ?>" placeholder="Contoh: Abu Bakar 01" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase mb-2 tracking-widest">Jenjang Kelas</label>
                        <select name="kelas" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none transition cursor-pointer">
                            <option value="Ula" <?php selected($kelas, 'Ula'); ?>>Ula (Dasar)</option>
                            <option value="Wustho" <?php selected($kelas, 'Wustho'); ?>>Wustho (Menengah)</option>
                            <option value="Ulya" <?php selected($kelas, 'Ulya'); ?>>Ulya (Atas)</option>
                        </select>
                    </div>
                </div>
                
                <div class="pt-4 flex items-center gap-4 border-t border-gray-50 mt-6">
                    <button type="submit" name="save_santri" class="bg-emerald-600 text-white px-10 py-3 rounded-xl font-black hover:bg-emerald-700 transition shadow-lg active:scale-95 uppercase text-xs tracking-widest">Simpan Data</button>
                    <a href="?page=ppm-santri" class="text-gray-400 text-xs font-bold uppercase tracking-widest hover:text-gray-600 transition">Batal</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}
