<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_submenu_page('ppm-dashboard', 'Laporan Bulanan', 'Laporan', 'manage_options', 'ppm-laporan', 'ppm_render_laporan_page');
});

function ppm_render_laporan_page() {
    global $wpdb;
    $table_keuangan = $wpdb->prefix . 'ppm_keuangan';
    
    // Ambil Statistik
    $total_masuk = $wpdb->get_var("SELECT SUM(jumlah) FROM $table_keuangan WHERE status = 'Lunas'");
    $total_tunggakan = $wpdb->get_var("SELECT SUM(jumlah) FROM $table_keuangan WHERE status = 'Belum Lunas'");
    $santri_baru = wp_count_posts('pendaftaran')->publish;

    ?>
    <div class="wrap mt-5 font-['Inter']">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Laporan Bulanan Pondok</h1>
            <button onclick="window.print()" class="bg-gray-800 text-white px-5 py-2 rounded-lg font-bold hover:bg-black transition">
                📥 Download PDF / Cetak
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-emerald-50 border border-emerald-100 p-6 rounded-2xl">
                <span class="text-emerald-600 font-semibold text-sm uppercase">Total Dana Masuk</span>
                <h2 class="text-3xl font-black text-emerald-800">Rp <?php echo number_format($total_masuk); ?></h2>
            </div>
            <div class="bg-rose-50 border border-rose-100 p-6 rounded-2xl">
                <span class="text-rose-600 font-semibold text-sm uppercase">Total Tunggakan</span>
                <h2 class="text-3xl font-black text-rose-800">Rp <?php echo number_format($total_tunggakan); ?></h2>
            </div>
            <div class="bg-blue-50 border border-blue-100 p-6 rounded-2xl">
                <span class="text-blue-600 font-semibold text-sm uppercase">Pendaftar Baru</span>
                <h2 class="text-3xl font-black text-blue-800"><?php echo $santri_baru; ?> Santri</h2>
            </div>
        </div>

        <div class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm">
            <h3 class="text-lg font-bold mb-4 text-gray-700 border-b pb-4">Rincian Arus Kas Terakhir</h3>
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-400 text-xs uppercase tracking-widest">
                        <th class="py-3">Deskripsi</th>
                        <th class="py-3">Status</th>
                        <th class="py-3 text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php 
                    $logs = $wpdb->get_results("SELECT * FROM $table_keuangan ORDER BY id DESC LIMIT 10");
                    foreach($logs as $log) : ?>
                    <tr>
                        <td class="py-4 text-sm font-medium text-gray-800"><?php echo $log->jenis_tagihan; ?></td>
                        <td class="py-4">
                            <span class="text-[10px] font-bold px-2 py-1 rounded <?php echo $log->status == 'Lunas' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                <?php echo strtoupper($log->status); ?>
                            </span>
                        </td>
                        <td class="py-4 text-right font-bold text-gray-900 italic">Rp <?php echo number_format($log->jumlah); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
