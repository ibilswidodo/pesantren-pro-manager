<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_submenu_page('ppm-dashboard', 'Cetak Kartu', 'Cetak Kartu', 'manage_options', 'ppm-kartu', 'ppm_render_kartu_page');
});

function ppm_render_kartu_page() {
    $santris = get_posts(['post_type' => 'santri', 'numberposts' => -1]);
    ?>
    <div class="wrap mt-5 font-['Inter']">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Pencetakan Kartu Santri</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 no-print">
            <?php foreach($santris as $s) : 
                $nis = get_post_meta($s->ID, '_ppm_nis', true);
                $kamar = get_post_meta($s->ID, '_ppm_kamar', true);
            ?>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col items-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full mb-4 flex items-center justify-center border-4 border-emerald-500">
                    <span class="text-emerald-500 font-bold">FOTO</span>
                </div>
                <h3 class="font-bold text-lg text-gray-800"><?php echo esc_html($s->post_title); ?></h3>
                <p class="text-sm text-gray-500 mb-4">NIS: <?php echo esc_html($nis); ?></p>
                
                <button onclick="window.print()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700 transition">
                    🖨️ Cetak Kartu
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <style>
            @media print {
                .no-print, #adminmenumain, #wpadminbar, .notice { display: none !important; }
                body { background: white; }
                .card-print {
                    width: 85mm;
                    height: 55mm;
                    border: 1px solid #ddd;
                    border-radius: 10px;
                    padding: 15px;
                    position: relative;
                    background: linear-gradient(135deg, #059669 15%, #ffffff 15%);
                    font-family: 'Inter', sans-serif;
                }
            }
        </style>
    </div>
    <?php
}
