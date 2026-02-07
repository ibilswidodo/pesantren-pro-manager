<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_submenu_page('ppm-dashboard', 'Laporan Bulanan', 'Laporan', 'manage_options', 'ppm-laporan', 'ppm_render_laporan_page');
});

function ppm_render_laporan_page() {
    global $wpdb;
    $table_keuangan = $wpdb->prefix . 'ppm_keuangan';
    
    // Ambil Statistik dengan fallback ke 0 jika null
    $total_masuk = $wpdb->get_var("SELECT SUM(jumlah) FROM $table_keuangan WHERE status = 'Lunas'") ?: 0;
    $total_tunggakan = $wpdb->get_var("SELECT SUM(jumlah) FROM $table_keuangan WHERE status = 'Belum Lunas'") ?: 0;
    $santri_baru = wp_count_posts('pendaftaran')->publish ?: 0;

    ?>
    <style>
        /* CSS Khusus agar hasil print bersih */
        @media print {
            #adminmenumain, #wpadminbar, #adminmenuwrap, #footer-thankyou, .update-nag, .notice, .bg-gray-800 {
                display: none !important;
            }
            #wpcontent { margin-left: 0 !important; padding: 0 !important; }
            .wrap { margin: 0 !important; }
            .rounded-2xl { border-radius: 0 !important; border: 1px solid #eee !important; }
            body { background: white !important; }
        }
    </style>

    <div class="wrap mt-5 font-['Inter']">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Laporan Bulanan Pondok</h1>
                <p class="text-sm text-gray-500">Dicetak pada: <?php echo date('d F Y'); ?></p>
            </div>
            <button onclick="window.print()" class="bg-gray-800 text-white px-5 py-2 rounded-lg font-bold hover:bg-black transition shadow-lg flex items-center gap-2">
                <span>📥</span> Download PDF / Cetak
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-emerald-50 border border-emerald-100 p-6 rounded-2xl shadow-sm">
                <span class="text-emerald-600 font-semibold text-xs uppercase tracking-wider">Total Dana Masuk</span>
                <h2 class="text-3xl font-black text-emerald-800">Rp <?php echo number_format($total_masuk, 0, ',', '.'); ?></h2>
            </div>
            <div class="bg-rose-50 border border-rose-100 p-6 rounded-2xl shadow-sm">
                <span class="text-rose-600 font-semibold text-xs uppercase tracking-wider">Total Tunggakan</span>
                <h2 class="text-3xl font-black text-rose-800">Rp <?php echo number_format($total_tunggakan, 0, ',', '.'); ?></h2>
            </div>
            <div class="bg-blue-50 border border-blue-100 p-6 rounded-2xl shadow-sm">
                <span class="text-blue-600 font-semibold text-xs uppercase tracking-wider">Pendaftar Baru</span>
                <h2 class="text-3xl font-black text-blue-800"><?php echo $santri_baru; ?> <span class="text-sm font-normal">Santri</span></h2>
            </div>
        </div>

        <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">
            <h3 class="text-lg font-bold mb-4 text-gray-700 border-b pb-4 flex justify-between">
                Rincian Arus Kas Terakhir
                <span class="text-xs font-normal text-gray-400">Menampilkan 10 transaksi terbaru</span>
            </h3>
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-400 text-xs uppercase tracking-widest border-b">
                        <th class="py-3">Deskripsi Tagihan</th>
                        <th class="py-3">Status Pembayaran</th>
                        <th class="py-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php 
                    $logs = $wpdb->get_results("SELECT * FROM $table_keuangan ORDER BY id DESC LIMIT 10");
                    if($logs) : foreach($logs as $log) : ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="py-4 text-sm font-medium text-gray-800"><?php echo esc_html($log->jenis_tagihan); ?></td>
                        <td class="py-4">
                            <span class="text-[10px] font-bold px-2 py-1 rounded-md <?php echo $log->status == 'Lunas' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                <?php echo strtoupper(esc_html($log->status)); ?>
                            </span>
                        </td>
                        <td class="py-4 text-right font-bold text-gray-900 italic">Rp <?php echo number_format($log->jumlah, 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="3" class="py-10 text-center text-gray-400">Belum ada data transaksi untuk dilaporkan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <p class="mt-8 text-center text-xs text-gray-400 show-on-print hidden">Laporan ini dibuat secara otomatis oleh Pesantren Pro Manager.</p>
    </div>
    <?php
}
