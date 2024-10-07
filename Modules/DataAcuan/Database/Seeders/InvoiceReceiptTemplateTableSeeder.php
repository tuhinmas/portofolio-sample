<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\InvoiceReceiptTemplate;

class InvoiceReceiptTemplateTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        InvoiceReceiptTemplate::updateOrCreate([
            "image_header_link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/nota/asset+template+pdf/Proforma.png",
            "image_footer_link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/nota/asset+template+pdf/Title.png",
            "image_logo_link" => "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/nota/asset+template+pdf/Logo+cetak.png",
            "note_payment_method" => "",
            "note_receving" => "",
            "note_sope" => "",
        ]);
    }
}
