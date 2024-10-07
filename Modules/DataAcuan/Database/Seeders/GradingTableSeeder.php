<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Grading;

class GradingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('gradings')->delete();
        $grades = [
            [
                "id" => 1,
                "name" => "Putih",
                "bg_color" => "#1a0d00",
                "fore_color" => "#4d2600",
                "bg_gradien" => 0,
                "max_unsettle_proformas" => 1,
                "maximum_payment_days" => 30,
                "credit_limit" => null,
                "action" => [
                    "maximum_order" => 30000000,
                ],
                "default" => "1",
                "description" => "Toko baru yang baru pertama ambil sebelum 30 hari setelah
                    di konfirmasi. Maksimal pengambilan 30 jt (tanpa PPN 10%) atau 1 invoice, dan
                    bila ada nota kedia maka pembelian pertama harus lunas terlebih dahulu",
            ],
            [
                "id" => 2,
                "name" => "Platinum",
                "bg_color" => "#999966",
                "fore_color" => "#b8b894",
                "bg_gradien" => 0,
                "max_unsettle_proformas" => 1,
                "maximum_payment_days" => 30,
                "credit_limit" => 1000000000,
                "description" => "Syarat Grade Platinum adalah pemebelian minimal 750 jt
                    (tanpa PPN 10%) dengan pembayaran cash 3x berturut-turut atau total pembelian 2,25 M
                    (tanpa PPN 10%) dalam pengambilan 4x berturut-turut. Barang dengan kategori B tidak termasuk dalam
                    perhitungan cash back",
            ],
            [
                "id" => 3,
                "name" => "Gold",
                "bg_color" => "#ffbf00",
                "fore_color" => "#ffdf80",
                "bg_gradien" => 0,
                "max_unsettle_proformas" => 1,
                "maximum_payment_days" => 30,
                "credit_limit" => 500000000,
                "description" => "Kredit limit tidak termasuk PPN 10%, dan jika pembayaran
                    menggunakan BG maka PPn harus di bayar di awal. Syarat menjadi Grade Gold adalah
                    pemebelian minimal 300 jt (Tanpa PPN 10%) dengan cash secara berturut-turut. Produk kategori
                    B tidak termasuk dalam perhitungan cash back.",
            ],
            [
                "id" => 4,
                "name" => "Silver",
                "bg_color" => "#c2c2a3",
                "fore_color" => "#ebebe0",
                "bg_gradien" => 0,
                "max_unsettle_proformas" => 1,
                "maximum_payment_days" => 30,
                "credit_limit" => 250000000,
                "description" => "Kredit limit tidak termasuk PPN 10%, dan jika pembayaran
                    menggunakan BG maka PPN harus di bayar di awal. Syarat menjadi Grade Silver adalah
                    pembelian 200 jt (tanpa PPN 10%) secara cash 3x berturut-turut. Barang kategori B tidak termasuk
                    dalam perhitungan cash back.",
            ],
            [
                "id" => 5,
                "name" => "Hijau",
                "bg_color" => "#39e600",
                "fore_color" => "#8cff66",
                "bg_gradien" => 0,
                "max_unsettle_proformas" => 1,
                "maximum_payment_days" => 30,
                "credit_limit" => null,
                "description" => "Toko prioritas dalam ketersediaan barang,
                    mendapat kesempatan 1x pengambilan dengan harga lama jika
                    terjadi kenaikan dengan periode tertentu",
            ],
            [
                "id" => 6,
                "name" => "Kuning",
                "bg_color" => "#ffff1a",
                "fore_color" => "#ffff80",
                "bg_gradien" => 0,
                "max_unsettle_proformas" => 1,
                "maximum_payment_days" => 30,
                "credit_limit" => 30000000,
                "description" => "Pengiriman barang setelah invoice sebelumnya lunas,
                    dapat mengikuti program promo setelah pembayaran invoice tepat waktu,
                    kredit limit dalam satu invoice tidak termasuk ppn, atau maksimal piutang
                    satu invoice sebelum pengambilan berikutnya. Syarat naik ke Grade Hijau adalah
                    pembelian total 50 jt (tanpa PPN) dengan pembayaran maksimal 30 hari",
            ],
            [
                "id" => 7,
                "name" => "Merah",
                "bg_color" => "#ff0066",
                "fore_color" => "#ff80b3",
                "bg_gradien" => 0,
                "max_unsettle_proformas" => 1,
                "maximum_payment_days" => 30,
                "credit_limit" => 0,
                "description" => "Toko dengan pembayaran bermasalah(lebih dari 3 bulan)
                    sulit di tagih tapi masih ada pembayaran. Permintaan barang selanjutnya bisa
                    dilakukan setelah invoice sebelumnya lunas. Grade ini tidak bisa mengikuti program
                    promo dan kontrak dealer yang diikuti secara administrasi akan hangus. Syarat naik ke Grade
                    Kuning adalah pembelian total 50 jt (tanpa PPN) dengan pembayaran cash",
            ],
            [
                "id" => 8,
                "name" => "Orange",
                "bg_color" => "#ff9900",
                "fore_color" => "#ffcc80",
                "bg_gradien" => 0,
                "max_unsettle_proformas" => 1,
                "maximum_payment_days" => 30,
                "credit_limit" => null,
                "description" => "Grade khusus untuk kenaikan dari merha ke kuning.
                    Grade Orange adalaha masa evaluasi untuk naik menjadi grade kuning
                    dengan syarat penjualan minimal 50 jt (tanpa PPN 10%) dengan pembayaran maksimal 30 hari.",
            ],
            [
                "id" => 9,
                "name" => "Hitam",
                "bg_color" => "#1a0d00",
                "fore_color" => "#4d2600",
                "bg_gradien" => 0,
                "max_unsettle_proformas" => 1,
                "maximum_payment_days" => 30,
                "credit_limit" => 0,
                "description" => "Toko yang tidak dapat di tagih dan sudah dilimpahkan
                    kasusnya ke bagian legasl/ Hukum Pemutusan Kerjasama",
            ],
        ];

        foreach ($grades as $grade) {
            Grading::firstOrCreate($grade);
        }
    }
}
