
<?php
if (!defined('ABSPATH')) exit;

// 1. Registrasi Submenu PPDB di Admin
add_action('admin_menu', function() {
    add_submenu_page(
        'ppm-dashboard', 
        'Pendaftaran Baru', 
        'Pendaftaran (PPDB)', 
        'manage_options', 
        'ppm-ppdb', 
        'ppm_render_ppdb_list'
    );
});

// 2. Tampilan Daftar Pendaftar di Admin
function ppm_render_ppdb_list() {
    $pendaftar = get_posts(['post_type' => 'pendaftaran', 'numberposts' => -1]);
    ?>
    <div class="wrap mt-5 font-['Inter']">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Calon Santri Baru (PPDB)</h1>
        
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-gray-600 text-sm uppercase">
                        <th class="p-4 font-semibold">Nama Calon</th>
                        <th class="p-4 font-semibold">Wali</th>
                        <th class="p-4 font-semibold">WhatsApp</th>
                        <th class="p-4 font-semibold">Status</th>
                        <th class="p-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($pendaftar) : foreach($pendaftar as $p) : 
                        $wa = get_post_meta($p->ID, '_ppm_ppdb_wa', true);
                        $wali = get_post_meta($p->ID, '_ppm_ppdb_wali', true);
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-bold text-gray-800"><?php echo esc_html($p->post_title); ?></td>
                        <td class="p-4 text-sm text-gray-600"><?php echo esc_html($wali); ?></td>
                        <td class="p-4 text-sm">
                            <a href="https://wa.me/<?php echo $wa; ?>" target="_blank" class="text-emerald-600 font-medium hover:underline">
                                <?php echo esc_html($wa); ?> ↗
                            </a>
                        </td>
                        <td class="p-4 text-sm">
                            <span class="px-2 py-1 rounded-full bg-orange-100 text-orange-700 text-xs font-bold">Menunggu</span>
                        </td>
                        <td class="p-4 text-center">
                            <button class="text-blue-600 hover:underline text-sm font-semibold">Detail / Proses</button>
                        </td>
                    </tr>
                    <?php endforeach; else : ?>
                    <tr><td colspan="5" class="p-10 text-center text-gray-400">Belum ada pendaftar baru.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// 3. Front-end Shortcode: [form_ppdb]
add_shortcode('form_ppdb', 'ppm_render_front_form');
function ppm_render_front_form() {
    ob_start();

    // Handler jika form disubmit
    if (isset($_POST['submit_ppdb'])) {
        $nama = sanitize_text_field($_POST['nama']);
        $new_id = wp_insert_post([
            'post_title' => $nama,
            'post_type'  => 'pendaftaran',
            'post_status' => 'publish'
        ]);

        if ($new_id) {
            update_post_meta($new_id, '_ppm_ppdb_wa', sanitize_text_field($_POST['wa']));
            update_post_meta($new_id, '_ppm_ppdb_wali', sanitize_text_field($_POST['wali']));
            echo '<div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded mb-6 text-center">Pendaftaran Berhasil! Admin kami akan menghubungi Anda segera.</div>';
        }
    }
    ?>
    <div class="ppm-form-container max-w-lg mx-auto bg-white p-8 rounded-2xl shadow-lg border border-gray-100 font-['Inter']">
        <h2 class="text-2xl font-bold text-emerald-800 mb-2 text-center">Form Pendaftaran</h2>
        <p class="text-gray-500 text-center mb-8 text-sm">Isi formulir pendaftaran santri baru di bawah ini.</p>
        
        <form method="post" action="" class="space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap Santri</label>
                <input type="text" name="nama" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Wali / Orang Tua</label>
                <input type="text" name="wali" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">No. WhatsApp Wali</label>
                <input type="tel" name="wa" placeholder="Contoh: 628123456789" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>
            <div class="pt-4">
                <button type="submit" name="submit_ppdb" class="w-full bg-emerald-600 text-white font-bold py-3 rounded-xl hover:bg-emerald-700 shadow-md transition-all active:scale-95">
                    Kirim Pendaftaran
                </button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
