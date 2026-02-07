<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_submenu_page('ppm-dashboard', 'Keuangan', 'Keuangan', 'manage_options', 'ppm-keuangan', 'ppm_render_keuangan');
});

function ppm_render_keuangan() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ppm_keuangan';

    // 1. LOGIC: Generate Tagihan Massal
    if (isset($_POST['generate_spp_massal'])) {
        check_admin_referer('ppm_finance_action', 'ppm_finance_nonce');
        
        $santris = get_posts(['post_type' => 'santri', 'numberposts' => -1]);
        $bulan_ini = date('F Y');
        $count = 0;

        foreach ($santris as $s) {
            // Cek apakah sudah ada tagihan bulan ini agar tidak double
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE santri_id = %d AND jenis_tagihan = %s",
                $s->ID, "SPP $bulan_ini"
            ));

            if (!$exists) {
                $wpdb->insert($table_name, [
                    'santri_id' => $s->ID,
                    'jenis_tagihan' => "SPP $bulan_ini",
                    'jumlah' => 500000, // Nominal default
                    'status' => 'Belum Lunas',
                    'tanggal_tagihan' => date('Y-m-d')
                ]);
                $count++;
            }
        }
        echo "<div class='updated'><p>Berhasil membuat $count tagihan SPP untuk bulan $bulan_ini.</p></div>";
    }

    // 2. LOGIC: Update Status atau Nominal
    if (isset($_POST['update_tagihan'])) {
        check_admin_referer('ppm_finance_action', 'ppm_finance_nonce');
        
        $wpdb->update($table_name, 
            ['jumlah' => sanitize_text_field($_POST['nominal']), 'status' => sanitize_text_field($_POST['status'])], 
            ['id' => intval($_POST['tagihan_id'])]
        );
        echo '<div class="updated"><p>Tagihan berhasil diperbarui!</p></div>';
    }

    $data = $wpdb->get_results("SELECT t.*, p.post_title FROM $table_name t JOIN {$wpdb->posts} p ON t.santri_id = p.ID ORDER BY t.id DESC");

    ?>
    <div class="wrap mt-5 font-['Inter']">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Manajemen Keuangan & SPP</h1>
            <form method="post">
                <?php wp_nonce_field('ppm_finance_action', 'ppm_finance_nonce'); ?>
                <button type="submit" name="generate_spp_massal" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700 transition">
                    ⚡ Generate SPP Bulan Ini
                </button>
            </form>
        </div>
        
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
                    <?php if($data): foreach ($data as $row) : ?>
                    <form method="post">
                        <?php wp_nonce_field('ppm_finance_action', 'ppm_finance_nonce'); ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 font-semibold text-gray-800"><?php echo esc_html($row->post_title); ?></td>
                            <td class="p-4 text-gray-600 text-sm"><?php echo esc_html($row->jenis_tagihan); ?></td>
                            <td class="p-4">
                                <input type="number" name="nominal" value="<?php echo (int)$row->jumlah; ?>" class="w-32 border rounded p-1 text-sm focus:ring-emerald-500 shadow-sm">
                            </td>
                            <td class="p-4">
                                <select name="status" class="text-sm border rounded p-1 shadow-sm <?php echo $row->status == 'Lunas' ? 'text-green-600 font-bold' : 'text-red-600'; ?>">
                                    <option value="Belum Lunas" <?php selected($row->status, 'Belum Lunas'); ?>>Belum Lunas</option>
                                    <option value="Lunas" <?php selected($row->status, 'Lunas'); ?>>Lunas</option>
                                </select>
                            </td>
                            <td class="p-4 text-center">
                                <input type="hidden" name="tagihan_id" value="<?php echo $row->id; ?>">
                                <button type="submit" name="update_tagihan" class="bg-emerald-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-emerald-700 transition">Update</button>
                            </td>
                        </tr>
                    </form>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="p-10 text-center text-gray-400">Belum ada data transaksi. Klik tombol biru untuk membuat tagihan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
