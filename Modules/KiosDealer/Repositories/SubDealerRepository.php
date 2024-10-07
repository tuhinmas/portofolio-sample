<?php

namespace Modules\KiosDealer\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Contest\Entities\ContestParticipant;
use Modules\KiosDealer\Entities\SubDealer;

class SubDealerRepository
{

    public function checkRequestOfChange($subDealerId)
    {
        $subDealer = SubDealer::withAggregate("subDealerTemp", "id")
            ->withAggregate("subDealerTemp", "name")
            ->find($subDealerId);

        $contest_participant = ContestParticipant::query()
            ->where("redeem_status", 1)
            ->whereHas("contest", function ($QQQ) {
                return $QQQ
                    ->where("period_date_start", "<=", now()->format("Y-m-d"))
                    ->where("period_date_end", ">=", now()->format("Y-m-d"));
            })

            ->where("participant_status", 4)
            ->where("participation_status", "!=", 4)->where("sub_dealer_id", $subDealer)->get()->count();

        $messageType = 0;
        $message = null;

        if ($subDealer) {
            if (in_array($subDealer->status, ['submission of changes', 'draft', 'transfered']) || $subDealer->subDealerTemp || $subDealer->dealerTemp) {
                if (in_array($subDealer->status, ['submission of changes'])) {
                    $message = 'Sub Dealer ' . $subDealer->name . ' sedang dalam perubahan';
                    $messageType = 1;
                } elseif (in_array(optional($subDealer->dealerTemp)->status, ['filed', 'wait approval']) || $subDealer->dealerTemp) {
                    $message = 'Sub Dealer ' . $subDealer->name . ' sedang dalam transfer';
                    $messageType = 2;
                } elseif ($subDealer->dealer_id != null) {
                    $message = 'Sub Dealer ' . $subDealer->name . ' sudah pernah diajukan sebagai Dealer';
                    $messageType = 3;
                } elseif ($subDealer->status == 'accepted' && optional($subDealer->subDealerTemp)->status == 'draft') {
                    $message = 'Sub Dealer ' . $subDealer->name . ' memiliki draft perubahan';
                    $messageType = 4;
                } elseif (optional($subDealer->dealerTemp)->status == 'draft' && $subDealer->status == 'accepted') {
                    $message = 'Sub Dealer ' . $subDealer->name . ' memiliki draft transfer';
                    $messageType = 5;
                }
                // elseif ($contest_participant > 0) {
                //     $message = 'Sub Dealer ' . $subDealer->name . ' sedang mengikuti Kontes!';
                //     $messageType = 7;
                // } 
                else {
                    $message = 'Sub Dealer ' . $subDealer->name . ' tidak bisa diubah / di transfer, hubungi admin';
                    $messageType = 6;
                }
                $response = [
                    'can_edit' => false,
                    'message' => $message,
                    'sub_dealer_temp_id' => $subDealer->sub_dealer_temp_id,
                    'sub_dealer_temp_name' => $subDealer->sub_dealer_temp_name,
                    'message_type' => $messageType
                ];
            } else {
                $response = [
                    'can_edit' => true,
                    'message' => 'yeee, You can edit this sub dealer',
                    'sub_dealer_temp_id' => $subDealer->sub_dealer_temp_id,
                    'sub_dealer_temp_name' => $subDealer->sub_dealer_temp_name,
                    'message_type' => $messageType
                ];
            }
        } else {
            $response = [
                'can_edit' => false,
                'sub_dealer_temp_id' => null,
                'sub_dealer_temp_name' => null,
                'message' => "sub dealer not found",
                'message_type' => 7
            ];
        }

        return $response;
    }

    public function fetchExport($params = [])
    {
        $query = "SELECT
            sub_dealers.sub_dealer_id,
            concat('CUST-SUB-', sub_dealers.sub_dealer_id) as cust_id,
            concat(
                COALESCE(sub_dealers.prefix, ''),
                ' ',
                COALESCE(sub_dealers.name, ''),
                ' ',
                COALESCE(sub_dealers.sufix, '')
            ) AS toko,
            personels.name as marketing,
            sub_dealers.owner,
            sub_dealers.telephone,
            sub_dealers.address as sub_dealer_address,
            sub_dealers.status as status,
            `ip`.`name` AS `propinsi`,
            `ic`.`name` AS `kota_kabupaten`,
            `ids`.`name` AS `kecamatan`,
            ifnull(`vdr`.`sub_region`, 'KANTOR') AS `group_rmc`,
            ifnull(`vdr`.`region`, 'KANTOR') AS `group_mdm`,
            `sub_dealers`.`owner_ktp` AS `owner_ktp`,
            `sub_dealers`.`owner_npwp` AS `owner_npwp`,
            `sub_dealers`.`owner_address` AS `owner_address`,
            `sub_dealers`.`owner_telephone` AS `owner_telephone`,
            `sub_dealers`.`id` AS `id`
        FROM
            sub_dealers
            LEFT JOIN personels ON personels.id = sub_dealers.personel_id
            AND personels.deleted_at IS NULL
            left join (
                select
                    `a`.`id` AS `id`,
                    `a`.`type` AS `type`,
                    `a`.`parent_id` AS `parent_id`,
                    `a`.`province_id` AS `province_id`,
                    `a`.`city_id` AS `city_id`,
                    `a`.`district_id` AS `district_id`,
                    `a`.`created_at` AS `created_at`,
                    `a`.`updated_at` AS `updated_at`,
                    `a`.`deleted_at` AS `deleted_at`
                from
                    `address_with_details` `a`
                where
                    (
                        (`a`.`type` = 'sub_dealer')
                        and isnull(`a`.`deleted_at`)
                    )
            ) `a` on((`a`.`parent_id` = sub_dealers.id))
            left join (
                select
                    `md`.`district_id` AS `district_id`,
                    `mr`.`id` AS `region_id`,
                    `mr`.`name` AS `region`,
                    `ms`.`id` AS `sub_region_id`,
                    `ms`.`name` AS `sub_region`
                from
                    (
                        (
                            `marketing_area_districts` `md`
                            join `marketing_area_sub_regions` `ms` on((`ms`.`id` = `md`.`sub_region_id`))
                        )
                        join `marketing_area_regions` `mr` on((`mr`.`id` = `ms`.`region_id`))
                    )
                where
                    (
                        isnull(`md`.`deleted_at`)
                        and isnull(`ms`.`deleted_at`)
                        and isnull(`mr`.`deleted_at`)
                    )
            ) as vdr on((`a`.`district_id` = `vdr`.`district_id`))
            left join `indonesia_provinces` `ip` on((`ip`.`id` = `a`.`province_id`))
            left join `indonesia_cities` `ic` on((`ic`.`id` = `a`.`city_id`))
            left join `indonesia_districts` `ids` on((`ids`.`id` = `a`.`district_id`))
        WHERE
            sub_dealers.deleted_at IS NULL
            AND (
                sub_dealers.status != 'transfered'
                OR sub_dealers.dealer_id IS NULL
            )";

        if (!empty($params['name'])) {
            $name = $params['name'];
            $query .= " and (
                CONCAT(
                    sub_dealers.prefix,
                    ' ',
                    sub_dealers.name,
                    ' ',
                    sub_dealers.sufix
                ) like '%$name%' or sub_dealer_id = '$name' or owner like '%$name%'
            ) ";
        }

        if (!empty($params['personnel_id'])) {
            $personnel = $params['personnel_id'];
            $query .= " and personels.id = '$personnel' ";
        }

        if (!empty($params['region_id'])) {
            $region = $params['region_id'];
            $query .= " and vdr.region_id = '$region' ";
        }

        $query .= "group by sub_dealers.id ORDER BY sub_dealers.sub_dealer_id ASC";
        return DB::select(DB::raw($query));
    }
}
