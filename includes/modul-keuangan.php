<?php
// Mencegah akses langsung
if (!defined('ABSPATH')) exit;

// Registrasi Menu Keuangan
add_action('admin_menu', function() {
    add_submenu_page('ppm-dashboard', 'Keuangan', 'Keuangan', 'manage_options', 'ppm-keuangan', 'ppm_render_keuangan');
});

// Fungsi Render Halaman Keuangan
function ppm_render_keuangan() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ppm_keuangan';

    // LOGIC: Update Status atau Nominal jika ada POST
    if (isset($_POST['update_tagihan'])) {
        $wpdb->update($table_name, 
            ['jumlah' => $_POST['nominal'], 'status' => $_POST['status']], 
            ['id' => $_POST['tagihan_id']]
        );
        echo '<div class="updated"><p>Tagihan berhasil diperbarui!</p></div>';
    }

    $data = $wpdb->get_results("SELECT t.*, p.post_title FROM $table_name t JOIN {$wpdb->posts} p ON t.santri_id = p.ID ORDER BY t.id DESC");

    ?>
    <div class="wrap mt-5 font-['Inter']">
        <h1 class="text-2xl font-bold mb-6 text-gray-800 tracking-tight">Manajemen Keuangan & SPP</h1>
        
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="p-4">Nama Santri</th>
                        <th class="p-4">Jenis Tagihan</th>
                        <th class="p-4">Nominal (Rp)</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($data as $row) : ?>
                    <form method="post">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 font-semibold text-gray-800"><?php echo $row->post_title; ?></td>
                            <td class="p-4 text-gray-600 text-sm"><?php echo $row->jenis_tagihan; ?></td>
                            <td class="p-4">
                                <input type="number" name="nominal" value="<?php echo (int)$row->jumlah; ?>" class="w-32 border rounded p-1 text-sm focus:ring-emerald-500">
                            </td>
                            <td class="p-4">
                                <select name="status" class="text-sm border rounded p-1">
                                    <option value="Belum Lunas" <?php selected($row->status, 'Belum Lunas'); ?>>Belum Lunas</option>
                                    <option value="Lunas" <?php selected($row->status, 'Lunas'); ?>>Lunas</option>
                                </select>
                            </td>
                            <td class="p-4 text-center">
                                <input type="hidden" name="tagihan_id" value="<?php echo $row->id; ?>">
                                <button type="submit" name="update_tagihan" class="bg-emerald-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-emerald-700">Update</button>
                            </td>
                        </tr>
                    </form>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
