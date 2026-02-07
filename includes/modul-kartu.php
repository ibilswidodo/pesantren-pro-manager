<?php
if (!defined('ABSPATH')) exit;

// 1. Registrasi Submenu Cetak Kartu
add_action('admin_menu', function() {
    add_submenu_page('ppm-dashboard', 'Cetak Kartu', 'Cetak Kartu', 'manage_options', 'ppm-kartu', 'ppm_render_kartu_page');
});

function ppm_render_kartu_page() {
    $santris = get_posts(['post_type' => 'santri', 'numberposts' => -1]);
    ?>
    <div class="wrap mt-5 font-['Inter']">
        <div class="flex justify-between items-center mb-6 no-print">
            <h1 class="text-2xl font-bold text-gray-800">Pencetakan Kartu Santri</h1>
            <p class="text-gray-500 text-sm italic">Klik tombol cetak untuk mencetak kartu masing-masing santri.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 no-print">
            <?php foreach($santris as $s) : 
                $nis = get_post_meta($s->ID, '_ppm_nis', true);
                $kamar = get_post_meta($s->ID, '_ppm_kamar', true);
            ?>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 flex flex-col items-center">
                <div class="w-20 h-20 bg-emerald-50 rounded-full mb-4 flex items-center justify-center border-2 border-emerald-500 overflow-hidden">
                    <span class="text-emerald-500 text-[10px] font-bold">FOTO</span>
                </div>
                <h3 class="font-bold text-lg text-gray-800"><?php echo esc_html($s->post_title); ?></h3>
                <p class="text-sm text-gray-400 mb-4">NIS: <?php echo esc_html($nis); ?></p>
                
                <button onclick="window.print()" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition">
                    🖨️ Cetak Kartu
                </button>

                <div class="only-print">
                    <div class="card-print">
                        <div class="card-header">
                            <strong>KARTU SANTRI</strong>
                            <small>Pondok Pesantren Digital</small>
                        </div>
                        <div class="card-body">
                            <div class="card-photo">FOTO</div>
                            <div class="card-info">
                                <h2><?php echo esc_html($s->post_title); ?></h2>
                                <p>NIS: <?php echo esc_html($nis); ?></p>
                                <p>Kamar: <?php echo esc_html($kamar); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <style>
            /* Sembunyikan elemen cetak di layar biasa */
            .only-print { display: none; }

            @media print {
                /* Sembunyikan elemen UI WordPress */
                .no-print, #adminmenumain, #wpadminbar, .notice, .flex, .wrap > h1 { display: none !important; }
                
                body { background: white; margin: 0; padding: 0; }
                .only-print { display: block !important; margin-bottom: 20px; page-break-inside: avoid; }
                
                /* Desain ID Card Standar (85mm x 55mm) */
                .card-print {
                    width: 85mm;
                    height: 55mm;
                    border: 1px solid #059669;
                    border-radius: 8px;
                    padding: 10px;
                    position: relative;
                    background: #fff;
                    font-family: 'Inter', sans-serif;
                    overflow: hidden;
                    box-sizing: border-box;
                }
                .card-header {
                    background: #059669;
                    color: white;
                    margin: -10px -10px 10px -10px;
                    padding: 5px 10px;
                    text-align: center;
                }
                .card-header strong { display: block; font-size: 14px; }
                .card-header small { font-size: 8px; letter-spacing: 1px; }
                .card-body { display: flex; gap: 10px; align-items: center; }
                .card-photo {
                    width: 25mm;
                    height: 30mm;
                    border: 1px solid #ccc;
                    background: #f9f9f9;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 8px;
                    color: #aaa;
                }
                .card-info h2 { margin: 0; font-size: 14px; color: #333; }
                .card-info p { margin: 2px 0; font-size: 10px; color: #666; }
            }
        </style>
    </div>
    <?php
}
