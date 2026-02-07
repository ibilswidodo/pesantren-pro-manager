<?php
if (!defined('ABSPATH')) exit;

// 1. Registrasi Menu & Submenu khusus Santri
add_action('admin_menu', function() {
    // Menu utama sudah dibuat di file utama, kita tinggal tambah submenu
    add_submenu_page('ppm-dashboard', 'Data Santri', 'Data Santri', 'manage_options', 'ppm-santri', 'ppm_render_santri_list');
    add_submenu_page('ppm-dashboard', 'Tambah Santri', 'Tambah Santri', 'manage_options', 'ppm-santri-add', 'ppm_render_santri_form');
});

// 2. Tampilan Daftar Santri (Table View)
function ppm_render_santri_list() {
    $santris = get_posts(['post_type' => 'santri', 'numberposts' => -1]);
    ?>
    <div class="wrap mt-5 font-['Inter']">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Daftar Santri Aktif</h1>
            <a href="?page=ppm-santri-add" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition shadow-sm"> + Tambah Santri </a>
        </div>

        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-gray-600 text-sm uppercase">
                        <th class="p-4 font-semibold">NIS</th>
                        <th class="p-4 font-semibold">Nama Lengkap</th>
                        <th class="p-4 font-semibold">Kamar</th>
                        <th class="p-4 font-semibold">Kelas</th>
                        <th class="p-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($santris as $s) : 
                        $nis = get_post_meta($s->ID, '_ppm_nis', true);
                        $kamar = get_post_meta($s->ID, '_ppm_kamar', true);
                        $kelas = get_post_meta($s->ID, '_ppm_kelas', true);
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-sm"><?php echo esc_html($nis); ?></td>
                        <td class="p-4 font-bold text-gray-800"><?php echo esc_html($s->post_title); ?></td>
                        <td class="p-4 text-sm text-gray-600"><?php echo esc_html($kamar); ?></td>
                        <td class="p-4 text-sm text-gray-600"><?php echo esc_html($kelas); ?></td>
                        <td class="p-4 text-center">
                            <a href="?page=ppm-santri-add&edit=<?php echo $s->ID; ?>" class="text-emerald-600 hover:underline font-medium">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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
    if (isset($_POST['save_santri'])) {
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
            echo '<div class="updated p-3 mb-4 bg-green-100 text-green-700 rounded border border-green-200">Data berhasil disimpan!</div>';
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Nama Lengkap Santri</label>
                        <input type="text" name="nama" value="<?php echo esc_attr($nama); ?>" required class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Nomor Induk Santri (NIS)</label>
                        <input type="text" name="nis" value="<?php echo esc_attr($nis); ?>" required class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Kamar / Asrama</label>
                        <input type="text" name="kamar" value="<?php echo esc_attr($kamar); ?>" class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2 text-gray-700">Jenjang Kelas</label>
                        <select name="kelas" class="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none transition">
                            <option value="Ula" <?php selected($kelas, 'Ula'); ?>>Ula</option>
                            <option value="Wustho" <?php selected($kelas, 'Wustho'); ?>>Wustho</option>
                            <option value="Ulya" <?php selected($kelas, 'Ulya'); ?>>Ulya</option>
                        </select>
                    </div>
                </div>
                
                <div class="pt-4 flex gap-3">
                    <button type="submit" name="save_santri" class="bg-emerald-600 text-white px-8 py-2.5 rounded-lg font-bold hover:bg-emerald-700 transition shadow-md">Simpan Data Santri</button>
                    <a href="?page=ppm-santri" class="bg-gray-100 text-gray-600 px-8 py-2.5 rounded-lg font-bold hover:bg-gray-200 transition">Kembali</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}
