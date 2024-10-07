<?php

namespace App\Exports;

use App\Traits\SalesOrderTrait;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\SalesOrder\Entities\SalesOrder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportDirectRetailer implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;
    use SalesOrderTrait;

    public function __construct(int $year)
    {
        $this->year = $year;
    }

    public function headings(): array
    {
        return [
            "Nama Marketing",
            "Jabatan",
            "Nominal",
            "Jumlah Order",
            "Bulan",
            "Tahun",
            "Data Per",
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $year = $this->year;
        $sales_orders = SalesOrder::query()
            ->with([
                "dealer" => function ($QQQ) {
                    return $QQQ->with([
                        "ditributorContract",
                    ]);
                },
                "invoice",
                "personel" => function ($QQQ) {
                    return $QQQ->with([
                        "position",
                    ]);
                },
            ])
            ->whereHas("invoice", function ($QQQ) use ($year) {
                return $QQQ->whereYear("created_at", $year);
            })
            ->whereHas("personel", function ($QQQ) {
                return $QQQ->whereHas("position");
            })
            ->where("type", "1")
            ->consideredOrder()
            ->whereHas("dealer", function ($QQQ) use ($year) {
                return $QQQ
                    ->whereHas("ditributorContract", function ($QQQ) use ($year) {
                        return $QQQ
                            ->whereYear("contract_start", $year)
                            ->orWhereYear("contract_end", $year);
                    })
                    ->orDoesntHave("ditributorContract");
            })
            ->get()
            ->sortBy([
                ["personel.name"],
                ["invoice.created_at"],
            ])
            ->reject(fn($order) => $this->orderInsideContractDistributor($order))
            ->groupBy([
                function ($val) {return $val->personel_id;},
                function ($val) {return $val->invoice->created_at->format("m");},
            ])
            ->map(function ($order_per_marketing) use ($year) {
                $order_per_marketing = $order_per_marketing->map(function ($order_per_month, $month) use ($year) {
                    $detail = [
                        "name" => $order_per_month->first()->personel->name,
                        "position" => $order_per_month->first()->personel->position->name,
                        "order_total" => $order_per_month->sum("invoice.total"),
                        "order_count" => $order_per_month->count(),
                        "month" => $month,
                        "year" => $year,
                        "data_per" => now()->format("Y-m-d"),
                    ];
                    return $detail;
                });

                return $order_per_marketing->values();
            })
            ->flatten(1)
            ->values();
        return $sales_orders;
    }

    public function map($data): array

    {
        return [
            $data["name"],
            $data["position"],
            $data["order_total"],
            $data["order_count"],
            $data["month"],
            $data["year"],
            $data["data_per"],
        ];
    }
}
