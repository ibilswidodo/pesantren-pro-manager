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
    // Handle Logic Terima Santri (Konfirmasi)
    if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
        $ppdb_id = intval($_GET['id']);
        $wa = get_post_meta($ppdb_id, '_ppm_ppdb_wa', true);
        $wali = get_post_meta($ppdb_id, '_ppm_ppdb_wali', true);
        $nama = get_the_title($ppdb_id);

        // Pindahkan ke CPT Santri
        $santri_id = wp_insert_post([
            'post_title'   => $nama,
            'post_type'    => 'santri',
            'post_status'  => 'publish'
        ]);

        if ($santri_id) {
            update_post_meta($santri_id, '_ppm_nis', 'REG-' . date('Y') . $santri_id); // Auto NIS Sementara
            update_post_meta($santri_id, '_ppm_wa_wali', $wa);
            update_post_meta($santri_id, '_ppm_wali', $wali);
            
            // Hapus data pendaftaran agar tidak menumpuk
            wp_delete_post($ppdb_id, true);
            echo '<div class="updated p-3 mb-4 bg-emerald-100 text-emerald-700 rounded border border-emerald-200 font-bold italic">Alhamdulillah! Santri bernama ' . esc_html($nama) . ' telah resmi diterima.</div>';
        }
    }

    $pendaftar = get_posts(['post_type' => 'pendaftaran', 'numberposts' => -1]);
    ?>
    <div class="wrap mt-5 font-['Inter']">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 tracking-tight text-center md:text-left">Calon Santri Baru (PPDB)</h1>
        
        <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-gray-600 text-xs uppercase tracking-widest">
                        <th class="p-4 font-semibold">Nama Calon</th>
                        <th class="p-4 font-semibold">Wali</th>
                        <th class="p-4 font-semibold">WhatsApp</th>
                        <th class="p-4 font-semibold">Status</th>
                        <th class="p-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php if ($pendaftar) : foreach($pendaftar as $p) : 
                        $wa = get_post_meta($p->ID, '_ppm_ppdb_wa', true);
                        $wali = get_post_meta($p->ID, '_ppm_ppdb_wali', true);
                    ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-bold text-gray-800"><?php echo esc_html($p->post_title); ?></td>
                        <td class="p-4 text-gray-600"><?php echo esc_html($wali); ?></td>
                        <td class="p-4">
                            <a href="https://wa.me/<?php echo $wa; ?>" target="_blank" class="text-emerald-600 font-medium hover:underline flex items-center gap-1">
                                <span>💬</span> <?php echo esc_html($wa); ?>
                            </a>
                        </td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-full bg-orange-100 text-orange-700 text-[10px] font-black uppercase">Pending Review</span>
                        </td>
                        <td class="p-4 text-center flex justify-center gap-3">
                            <a href="?page=ppm-ppdb&action=approve&id=<?php echo $p->ID; ?>" onclick="return confirm('Apakah Anda yakin santri ini diterima?')" class="bg-emerald-600 text-white px-3 py-1.5 rounded-md text-xs font-bold hover:bg-emerald-700 transition shadow-sm">Terima</a>
                            <a href="<?php echo get_delete_post_link($p->ID); ?>" class="text-rose-500 hover:text-rose-700 text-xs font-bold p-1.5 underline" onclick="return confirm('Tolak pendaftaran?')">Tolak</a>
                        </td>
                    </tr>
                    <?php endforeach; else : ?>
                    <tr><td colspan="5" class="p-10 text-center text-gray-400 italic">Belum ada calon santri yang mendaftar melalui form website.</td></tr>
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
            echo '<div class="bg-emerald-50 border border-emerald-500 text-emerald-800 px-6 py-4 rounded-xl mb-8 text-center shadow-lg font-bold">🎉 Pendaftaran Berhasil! Pengurus kami akan menghubungi Anda melalui WhatsApp segera.</div>';
        }
    }
    ?>
    <div class="ppm-form-container max-w-lg mx-auto bg-white p-10 rounded-3xl shadow-2xl border border-gray-100 font-['Inter'] transition-all">
        <h2 class="text-3xl font-black text-emerald-900 mb-2 text-center tracking-tight">Formulir Pendaftaran</h2>
        <p class="text-gray-400 text-center mb-10 text-sm">Digitalisasi Pondok: Cepat, Transparan, Berkah.</p>
        
        <form method="post" action="" class="space-y-6">
            <div>
                <label class="block text-xs font-black text-emerald-700 uppercase mb-2 tracking-widest">Nama Lengkap Santri</label>
                <input type="text" name="nama" placeholder="Tulis nama sesuai Akta" required class="w-full p-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 outline-none transition text-gray-800 font-medium placeholder:text-gray-300">
            </div>
            <div>
                <label class="block text-xs font-black text-emerald-700 uppercase mb-2 tracking-widest">Nama Wali / Orang Tua</label>
                <input type="text" name="wali" placeholder="Ayah / Ibu / Kakak" required class="w-full p-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 outline-none transition text-gray-800 font-medium placeholder:text-gray-300">
            </div>
            <div>
                <label class="block text-xs font-black text-emerald-700 uppercase mb-2 tracking-widest">No. WhatsApp Wali</label>
                <input type="tel" name="wa" placeholder="Contoh: 628123456789" required class="w-full p-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 outline-none transition text-gray-800 font-medium placeholder:text-gray-300">
            </div>
            <div class="pt-6">
                <button type="submit" name="submit_ppdb" class="w-full bg-emerald-600 text-white font-black py-4 rounded-2xl hover:bg-emerald-700 shadow-[0_10px_30px_rgba(5,150,105,0.3)] transition-all active:scale-95 text-lg">
                    Kirim Pendaftaran 🚀
                </button>
                <p class="mt-4 text-[10px] text-gray-400 text-center uppercase tracking-widest">Data dijamin aman & terlindungi oleh sistem pesantren.</p>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
