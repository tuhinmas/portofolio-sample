<?php

namespace Modules\DistributionChannel\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderFile;
use ogrrd\CsvIterator\CsvIterator;

class DispatchOrderFileTableSeeder extends Seeder
{
    public function __construct(
        protected DispatchOrderFile $dispatch_order_file,
        protected DispatchOrder $dispatch_order,
    ) {}
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $pathToFile = 'Modules/DistributionChannel/Database/Seeders/csv/export lampiran SJ.csv';
        $delimiter = ','; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        foreach ($rows as $row) {
            $dispatch_order = $this->dispatch_order->query()
                ->whereHas("deliveryOrder", function ($QQQ) use ($row) {
                    return $QQQ->where("delivery_order_number", "like", "%" . $row["delivery_order_number"]);
                })
                ->first();

            if (!$dispatch_order) {
                dd([
                    $row["delivery_order_number"] => "dispatch not found",
                ]);
            }

            $dispatch_order_file = $this->dispatch_order_file->firstOrCreate([
                "dispatch_orders_id" => $dispatch_order->id,
                "document" => $row["file"],
                "caption" => $row["caption"],
            ]);

            dump([
                "sj" => $row["delivery_order_number"],
                "document" => $row["file"],
                "caption" => $row["caption"],
            ]);
        }
    }
}
