<?php

namespace Modules\Personel\Repositories;

use App\Traits\ChildrenList;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\District;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\Division;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelSimple;
use Modules\Personel\Repositories\Traits\MandatoryProductAchievementRepository;
use PDO;

class PersonelRepository
{

    use MandatoryProductAchievementRepository;

    public function fetchPersonnelSimple($params = [])
    {
        $query = Personel::withoutAppends()        
            ->with(['position' => function ($q) {
                $q->select('id', 'name');
            }])
            ->when(!empty($params["scope_supervisor"]), function ($query) {
                return $query->supervisor();
            })
            ->when(!empty($params["scopeMarketingUnderSupervisor"]), function ($query) {

                return $query->marketingMarketingUnderSupervisor();
            })
            ->when(!empty($params["scopePermissionPickUp"]) && $params["scopePermissionPickUp"] == true, function ($query) {
                return $query->whereHas("user", function ($query) {
                    return $query->whereHas('permissions', function ($query) {
                        $query->whereIn('name', ['(S) Pick Up Order']);
                    });
                });
            })

            ->when(empty($params["scopePermissionPickUp"]), function ($query) {
                return $query->whereHas('position', function ($q) {
                    return $q->whereIn('name', marketing_positions());
                    // ->where('name', '!=', 'aplikator');
                });
            })
            
            ->whereIn('status', [1, 2])
            ->when(!empty($params['name']), function ($q) use ($params) {
                return $q->where('name', 'like', '%' . $params['name'] . '%');
            })
            ->when(!empty($params['position_name']), function ($q) use ($params) {
                $q->whereHas('position', function ($q) use ($params) {
                    return $q->where('name', 'like', '%' . $params['position_name'] . '%');
                });
            })
            ->select('id', 'name', 'position_id')
            ->orderBy('name', ($params['sort'] ?? 'asc'));


        if (!empty($params['limit'])) {
            return $query->paginate($params['limit']);
        }

        return $query->get();
    }

    public function checkPersonelDisable($personelId)
    {
        $marketingRegionIsActive = Region::where('personel_id', $personelId)->first();
        if ($marketingRegionIsActive) {
            return [
                'can_non_active' => false,
                'title' => 'MM Masih Memegang Region',
                'message' => 'Pastikan MM Tidak Memegang Region terlebih dahulu!',
            ];
        }

        $anotherMarketingMM = Personel::whereHas('position', function ($q) {
            $q->where('is_mm', true);
        })->whereIn('status', [1])->where('id', '<>', $personelId)->get()->count();

        if ($anotherMarketingMM == 0) {
            return [
                'can_non_active' => false,
                'title' => 'Tidak Ditemukan Pengganti MM',
                'message' => 'Tidak ditemukan marketing pengganti, Penonaktifkan MM Gagal',
            ];
        }

        return [
            'can_non_active' => true,
            'title' => 'Bisa di non aktif kan',
            'message' => 'Personel Bisa di nonaktifkan',
        ];
    }

    public function recapPersonel4Mount($params = [])
    {
        if (!empty($params['division_id'])) {
            $divisionId = "'" . implode("','", $params['division_id']) . "'";
        } else {
            $division = Division::where('name', 'Sales & Marketing')->first();
            $divisionId = "'$division->id'";
        }

        $personel_ids = [];
        if (isset($params["scope_supervisor"])) {
            $personel_ids = (new class
            {
                use ChildrenList;
            })->getChildren($params["scope_supervisor"]);
        }
        $result = DB::table('sales_orders as s')
            ->select(
                's.personel_id',
                'p.name',
                DB::raw('IF(p.status = 1, "aktif", IF(p.status = 2, "freeze", "Out")) AS status_marketing'),
                'join_date',
                'resign_date',
                DB::raw('(
                    CASE
                        WHEN p.target IS NOT NULL THEN p.target
                        WHEN sub_region_target IS NOT NULL THEN sub_region_target
                        WHEN region_target IS NOT NULL THEN region_target
                        ELSE p.target
                    END) AS target'),
                'region',
                'sub_region',
                'jabatan as position',
                DB::raw('SUM(CASE WHEN (DATE(s.date) BETWEEN (LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 4 MONTH)) + INTERVAL 1 DAY) AND LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH))) OR (DATE(i.created_at) BETWEEN (LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 4 MONTH)) + INTERVAL 1 DAY) AND LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH))) THEN s.sub_total - IFNULL(s.discount, 0) ELSE 0 END) AS bulan_min_3'),
                DB::raw('SUM(CASE WHEN (DATE(s.date) BETWEEN (LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH)) + INTERVAL 1 DAY) AND LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH))) OR (DATE(i.created_at) BETWEEN (LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH)) + INTERVAL 1 DAY) AND LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH))) THEN s.sub_total - IFNULL(s.discount, 0) ELSE 0 END) AS bulan_min_2'),
                DB::raw('SUM(CASE WHEN (DATE(s.date) BETWEEN (LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)) + INTERVAL 1 DAY) AND LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH))) OR (DATE(i.created_at) BETWEEN (LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH)) + INTERVAL 1 DAY) AND LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH))) THEN s.sub_total - IFNULL(s.discount, 0) ELSE 0 END) AS bulan_min_1'),
                DB::raw('SUM(CASE WHEN (DATE(s.date) BETWEEN (LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) + INTERVAL 1 DAY) AND LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 0 MONTH))) OR (DATE(i.created_at) BETWEEN (LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) + INTERVAL 1 DAY) AND LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 0 MONTH))) THEN s.sub_total - IFNULL(s.discount, 0) ELSE 0 END) AS bulan_min_0'),
                DB::raw('MONTHNAME(LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH))) AS bulan_name_3'),
                DB::raw('MONTHNAME(LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 2 MONTH))) AS bulan_name_2'),
                DB::raw('MONTHNAME(LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH))) AS bulan_name_1'),
                DB::raw('MONTHNAME(LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 0 MONTH))) AS bulan_name_0')
            )
            ->leftJoin('invoices as i', function ($join) {
                $join->on('i.sales_order_id', '=', 's.id')
                    ->whereNull('i.deleted_at');
            })
            ->join(DB::raw('(SELECT
                            a.parent_id as store_id,
                            ms.id as sub_region_id,
                            ms.name as sub_region,
                            mr.id as region_id,
                            mr.name as region,
                            ms.target as sub_region_target,
                            mr.target as region_target
                        FROM
                            address_with_details as a
                            JOIN marketing_area_districts as md ON md.district_id = a.district_id AND md.deleted_at IS NULL
                            JOIN marketing_area_sub_regions as ms ON ms.id = md.sub_region_id AND md.deleted_at IS NULL
                            JOIN marketing_area_regions as mr ON mr.id = ms.region_id AND md.deleted_at IS NULL
                        WHERE
                            a.deleted_at IS NULL
                            AND a.type IN ("dealer", "sub_dealer")
                        GROUP BY
                            parent_id) as ar'), 'ar.store_id', '=', 's.store_id')
            ->join(DB::raw('(SELECT
                            m.id,
                            m.name,
                            m.target,
                            p.name as jabatan,
                            m.status,
                            m.join_date,
                            m.resign_date
                        FROM
                            personels as m
                            JOIN positions as p ON p.id = m.position_id AND p.division_id IN (' . $divisionId . ') AND p.deleted_at IS NULL
                        WHERE
                            m.deleted_at IS NULL
                            AND (m.resign_date IS NULL OR m.resign_date > (LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 4 MONTH))))
                        GROUP BY
                            m.id) as p'), 'p.id', '=', 's.personel_id')
            ->leftJoin(DB::raw('(SELECT
                            o.id
                        FROM
                            sales_orders as o
                            JOIN distributor_contracts as d ON d.dealer_id = o.store_id
                            LEFT JOIN invoices as i ON i.sales_order_id = o.id
                        WHERE
                            o.deleted_at IS NULL
                            AND i.deleted_at IS NULL
                            AND d.deleted_at IS NULL
                            AND ((o.date BETWEEN d.contract_start AND d.contract_end) OR (i.created_at BETWEEN d.contract_start AND contract_end))
                            AND o.status IN ("confirmed", "returned", "pending")) as ds'), 'ds.id', '=', 's.id')
            ->whereNull('s.deleted_at')
            ->where('s.is_office', 0)
            ->where(function ($query) {
                $query->whereBetween(DB::raw('DATE(s.date)'), [
                    DB::raw('(LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 4 MONTH)) + INTERVAL 1 DAY)'),
                    DB::raw('LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 0 MONTH))'),
                ])
                    ->orWhereBetween(DB::raw('DATE(i.created_at)'), [
                        DB::raw('(LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 4 MONTH)) + INTERVAL 1 DAY)'),
                        DB::raw('LAST_DAY(DATE_SUB(CURRENT_DATE, INTERVAL 0 MONTH))'),
                    ]);
            })
            ->whereIn('s.status', ['confirmed', 'pending', 'returned'])
            ->whereNull('ds.id')
            ->whereNotNull('s.personel_id')
            ->when(!empty($params['region_id']), function ($q) use ($params) {
                $q->whereIn('ar.region_id', $params['region_id']);
            })
            ->when(!empty($params['sub_region_id']), function ($q) use ($params) {
                $q->whereIn('ar.sub_region_id', $params['sub_region_id']);
            })
            ->when(!empty($params['status']), function ($q) use ($params) {
                $q->whereIn('p.status', $params['status']);
            })
            ->when(!empty($params['personel_id']), function ($q) use ($params) {
                $q->whereIn('s.personel_id', $params['personel_id']);
            })

            /* filter marketing as supervisor */
            ->when(!empty($params['scope_supervisor']), function ($q) use ($personel_ids) {
                $q->whereIn('s.personel_id', $personel_ids);
            })
            ->groupBy('s.personel_id')
            ->orderBy('p.name')
            ->get();

        return $result;
    }

    public function storeCoverage($params = [], $personelId)
    {
        $perPage = $params['limit'] ?? 30;
        $currentPage = $params['page'] ?? 1; // Halaman saat ini
        $page = $params['page'] ?? 1;
        $offset = ($currentPage - 1) * $perPage;
        $userLogin = auth()->user()->personel_id;
        $personelsArray[] = $personelId;

        if (!empty($params['scopes'])) {
            foreach (($params['scopes'] ?? []) as $key => $scope) {
                if ($scope == "supervisor") {
                    $personelsArray = personel_with_child($personelId);
                }
            }
        } elseif (!empty($params['filter'])) {
            foreach ($params['filter'] as $key => $value) {
                if ($value['field'] == 'marketing_id') {
                    $personelsArray = $value['value'];
                }else{
                    $personelsArray = [$personelId];
                }
            }
        } else{
            $personelsArray = [$personelId];
        }

        $personels = "'" . implode("','", $personelsArray) . "'";
        $filterPersonels = "and (
            mar.personel_id in ($personels)
            or mas.personel_id in ($personels)
            or mad.personel_id in ($personels)
            or mad.applicator_id in ($personels)
        )";

        $query = "SELECT
            DISTINCT
            t.*,
            ar.`viewer`,
            gr.name as grading_name,
            gr.`bg_color`,
            gr.`bg_gradien`,
            gr.`fore_color`,
            ar.`region`,
            ar.`region_id`,
            ar.`sub_region`,
            ar.`sub_region_id`,
            ar.`province`,
            ar.`city`,
            ar.`district`,
            ar.`district_id`,
            s.`first_sales`,
            s.`last_sales`,
            s.`sales`,
            s.`sales_return`,
            s.`nominal`,
            s.`0_90`,
            s.`91_180`,
            s.`181_270`,
            s.`271_360`,
            s.`361_n`,
            mr_p.name as position_name,
            mr.name as personel_name
        FROM
            (
                select
                    '1' type_dealer,
                    tk.id `toko_id`,
                    dealer_id `cust_number`,
                    CONCAT('CUST-', dealer_id) `cust_id`,
                    IF(
                        tk.name = '-'
                        or tk.name = null,
                        CONCAT('(', tk.owner, ')'),
                        CONCAT(
                            case
                                when tk.prefix is null then ''
                                else CONCAT(tk.prefix, ' ')
                            end,
                            tk.name,
                            case
                                when tk.sufix is null then ''
                                else CONCAT(' ', tk.sufix)
                            end
                        )
                    ) `toko_name`,
                    al.name `level_toko`,
                    tk.`grading_id`,
                    date(tk.created_at) `registered_at`,
                    blocked_at,
                    owner,
                    personel_id
                from
                    dealers as tk
                    join agency_levels as al on al.id = tk.agency_level_id
                    and al.deleted_at is null
                where
                    tk.deleted_at is null
                UNION
                select
                    '2' type_dealer,
                    id `toko_id`,
                    sub_dealer_id `cust_number`,
                    CONCAT('CUST-SUB-', sub_dealer_id) `cust_id`,
                    IF(
                        tk.name = '-'
                        or tk.name = null,
                        CONCAT('(', tk.owner, ')'),
                        CONCAT(
                            case
                                when tk.prefix is null then ''
                                else CONCAT(tk.prefix, ' ')
                            end,
                            tk.name,
                            case
                                when tk.sufix is null then ''
                                else CONCAT(' ', tk.sufix)
                            end
                        )
                    ) `toko_name`,
                    null `level_toko`,
                    tk.`grading_id`,
                    date(tk.created_at) `registered_at`,
                    blocked_at,
                    owner,
                    personel_id
                from
                    sub_dealers as tk
                where
                    tk.deleted_at is null
            ) as t
            join personels as mr on mr.id = t.personel_id
            join positions as mr_p on mr_p.id = mr.position_id
            join gradings as gr on gr.id = t.grading_id
            join address_with_details as ad on ad.parent_id = t.toko_id
            and (ad.`type` IN ('dealer', 'sub_dealer'))
            and ad.deleted_at is null
            join (
                select
                    mar.id `region_id`,
                    mar.name `region`,
                    mas.id `sub_region_id`,
                    mas.name `sub_region`,
                    ip.id `province_id`,
                    ip.name `province`,
                    ic.id `city_id`,
                    ic.name `city`,
                    id.id `district_id`,
                    id.name `district`,
                    po.name `viewer`
                from
                    marketing_area_districts as mad
                    join marketing_area_sub_regions as mas on mas.id = mad.sub_region_id
                    join marketing_area_regions as mar on mar.id = mas.region_id
                    join indonesia_districts as id on id.id = mad.district_id
                    join indonesia_cities as ic on ic.id = mad.city_id
                    join indonesia_provinces as ip on ip.id = mad.province_id
                    join personels as pe on pe.id = '$userLogin'
                    join positions as po on po.id = pe.position_id
                where
                    mad.deleted_at is null
                    and mas.deleted_at is null
                    and mar.deleted_at is null
                    $filterPersonels
            ) as ar on ar.district_id = ad.district_id
            left join (
                SELECT
                    store_id,
                    min(date(COALESCE(i.created_at, so.date))) `first_sales`,
                    max(date(COALESCE(i.created_at, so.date))) `last_sales`,
                    count(DISTINCT so.id) `sales`,
                    COUNT(
                        DISTINCT CASE
                            WHEN so.status = 'returned' THEN so.`id`
                        END
                    ) AS `sales_return`,
                    sum(
                        (
                            sd.unit_price * (sd.quantity - IFNULL(sd.returned_quantity, 0))
                        ) - IFNULL(sd.discount, 0)
                    ) `nominal`,
                    sum(
                        case
                            when DATE(COALESCE(i.created_at, so.date)) between DATE_SUB(
                                DATE(CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')),
                                INTERVAL 90 DAY
                            )
                            and DATE(CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')) then (
                                sd.unit_price * (sd.quantity - IFNULL(sd.returned_quantity, 0))
                            ) - IFNULL(sd.discount, 0)
                            else 0
                        end
                    ) `0_90`,
                    sum(
                        case
                            when DATE(COALESCE(i.created_at, so.date)) between DATE_SUB(
                                DATE(CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')),
                                INTERVAL 180 DAY
                            )
                            and DATE_SUB(
                                DATE(CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')),
                                INTERVAL 91 DAY
                            ) then (
                                sd.unit_price * (sd.quantity - IFNULL(sd.returned_quantity, 0))
                            ) - IFNULL(sd.discount, 0)
                            else 0
                        end
                    ) `91_180`,
                    sum(
                        case
                            when DATE(COALESCE(i.created_at, so.date)) between DATE_SUB(
                                DATE(CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')),
                                INTERVAL 270 DAY
                            )
                            and DATE_SUB(
                                DATE(CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')),
                                INTERVAL 181 DAY
                            ) then (
                                sd.unit_price * (sd.quantity - IFNULL(sd.returned_quantity, 0))
                            ) - IFNULL(sd.discount, 0)
                            else 0
                        end
                    ) `181_270`,
                    sum(
                        case
                            when DATE(COALESCE(i.created_at, so.date)) between DATE_SUB(
                                DATE(CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')),
                                INTERVAL 360 DAY
                            )
                            and DATE_SUB(
                                DATE(CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')),
                                INTERVAL 271 DAY
                            ) then (
                                sd.unit_price * (sd.quantity - IFNULL(sd.returned_quantity, 0))
                            ) - IFNULL(sd.discount, 0)
                            else 0
                        end
                    ) `271_360`,
                    sum(
                        case
                            when DATE(COALESCE(i.created_at, so.date)) < DATE_SUB(
                                DATE(CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')),
                                INTERVAL 360 DAY
                            ) then (
                                sd.unit_price * (sd.quantity - IFNULL(sd.returned_quantity, 0))
                            ) - IFNULL(sd.discount, 0)
                            else 0
                        end
                    ) `361_n`
                FROM
                    sales_order_details as sd
                    join sales_orders as so on so.id = sd.sales_order_id
                    left join invoices as i on i.sales_order_id = so.id
                where
                    sd.deleted_at is null
                    and so.deleted_at is null
                    and so.status IN ('confirmed', 'pending', 'returned')
                    and i.deleted_at is null
                group by
                    store_id
            ) as s on s.store_id = t.toko_id where ";

        function removeElementsByField($array, $field)
        {
            $newArray = [];
            foreach ($array as $item) {
                if ($item['field'] !== $field) {
                    $newArray[] = $item;
                }
            }
            return $newArray;
        }

        if (!empty($params['filter'])) {
            $filters = removeElementsByField($params['filter'], "marketing_id");
        }

        if (auth()->user()->personel->position->name == "Aplikator") {
            $query .= " case
                    when viewer = 'Regional Marketing (RM)' then level_toko not in ('D2','D1') or type_dealer = 2
                    when viewer = 'Regional Marketing Coordinator (RMC)' then level_toko not in ('D1') or type_dealer = 2
                    when viewer = 'Marketing District Manager (MDM)' then true
                END ";
        } else {
            $query .= " case
                when viewer = 'Regional Marketing (RM)' then level_toko not in ('D2','D1') or type_dealer = 2
                when viewer = 'Regional Marketing Coordinator (RMC)' then level_toko not in ('D1') or type_dealer = 2
                ELSE true
            END";
        }

        if (isset($filters)) {
            if (count($filters) == 1) {
                foreach ($filters as $key => $row) {
                    $field = $row['field'];
                    if (in_array($field, $this->selectCoverageName())) {
                        $action = $row['action'];
                        $value = $row['value'];
                        if ($action == 'like') {
                            $query .= " and $field like '%$value%' ";
                        } elseif ($action == 'in') {
                            if (count($value) > 0) {
                                switch ($field) {
                                    case 'level_toko':
                                        $in = "'" . implode("','", $value) . "'";
                                        $query .= " and ($field in ($in) or $field is null) ";    
                                        break;
                                    case 'region_id':
                                        $in = "'" . implode("','", $value) . "'";
                                        $query .= " and ar.region_id in ($in) ";
                                        break;
                                    case 'sub_region_id':
                                        $in = "'" . implode("','", $value) . "'";
                                        $query .= " and ar.sub_region_id in ($in) ";
                                        break;
                                    default:
                                        $in = "'" . implode("','", $value) . "'";
                                        $query .= " and $field in ($in) ";
                                        break;
                                }
                            }
                        } else {
                            $query .= " and $field $action '$value' ";
                        }
                    }
                }
            } else {
                foreach ($filters as $key => $row) {
                    if ($key == 0) {
                        $field = $row['field'];
                        if (in_array($field, $this->selectCoverageName())) {
                            $action = $row['action'];
                            $value = $row['value'];
                            if ($action == 'like') {
                                $query .= " and $field like '%$value%' ";
                            } elseif ($action == 'in') {
                                if (count($value) > 0) {
                                    switch ($field) {
                                        case 'level_toko':
                                            $in = "'" . implode("','", $value) . "'";
                                            $query .= " and ($field in ($in) or $field is null) ";    
                                            break;
                                        case 'region_id':
                                            $in = "'" . implode("','", $value) . "'";
                                            $query .= " and ar.region_id in ($in) ";
                                            break;
                                        case 'sub_region_id':
                                            $in = "'" . implode("','", $value) . "'";
                                            $query .= " and ar.sub_region_id in ($in) ";
                                            break;
                                        default:
                                            $in = "'" . implode("','", $value) . "'";
                                            $query .= " and $field in ($in) ";
                                            break;
                                    }
                                }
                            } else {
                                $query .= " and $field $action '$value' ";
                            }
                        }
                    } else {
                        $field = $row['field'];
                        if (in_array($field, $this->selectCoverageName())) {
                            $action = $row['action'];
                            $value = $row['value'];
                            if ($action == 'like') {
                                $query .= " and $field like '%$value%' ";
                            } elseif ($action == 'in') {
                                if (count($value) > 0) {
                                    switch ($field) {
                                        case 'level_toko':
                                            $in = "'" . implode("','", $value) . "'";
                                            $query .= " and ($field in ($in) or $field is null) ";    
                                            break;
                                        case 'region_id':
                                            $in = "'" . implode("','", $value) . "'";
                                            $query .= " and ar.region_id in ($in) ";
                                            break;
                                        case 'sub_region_id':
                                            $in = "'" . implode("','", $value) . "'";
                                            $query .= " and ar.sub_region_id in ($in) ";
                                            break;
                                        default:
                                            $in = "'" . implode("','", $value) . "'";
                                            $query .= " and $field in ($in) ";
                                            break;
                                    }
                                }
                            } else {
                                $query .= " and $field $action '$value' ";
                            }
                        }
                    }
                }
            }
        }

        $query .= " group by ad.parent_id";

        if (isset($params['sort'])) {
            $sortStrings = [];
            foreach ($params['sort'] as $sortItem) {
                $sortStrings[] = $sortItem['field'] . ' ' . $sortItem['direction'];
            }
            $mergedString = implode(', ', $sortStrings);
            $query .= " order by $mergedString";
        }

        $data = DB::select(DB::raw($query));

        if (!empty($params['disable_pagination'])) {
            return $data;
        }

        $offset = ($page - 1) * $perPage;
        $countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
        $total = DB::select(DB::raw($countQuery))[0]->total;
        $query .= " LIMIT :perPage OFFSET :offset";
        $data = DB::select(DB::raw($query), [
            'perPage' => $perPage,
            'offset' => $offset,
        ]);

        return new LengthAwarePaginator($data, $total, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    private function selectCoverageName()
    {
        return [
            "grading_name",
            "type_dealer",
            "toko_id",
            "toko_number",
            "cust_id",
            "toko_name",
            "level_toko",
            "grading_id",
            "registered_at",
            "bg_color",
            "bg_gradien",
            "fore_color",
            "region",
            "region_id",
            "sub_region",
            "sub_region_id",
            "province",
            "city",
            "district",
            "ar.district_id",
            "first_sales",
            "last_sales",
            "sales",
            "sales_return",
            "nominal",
            "0_90",
            "91_180",
            "181_270",
            "271_360",
            "361_n",
        ];
    }

    public function storeCoverageFilter($params = [], $personelId)
    {
        $personels = Personel::select('id')
            ->where('id', $personelId)
            ->orWhere('supervisor_id', $personelId)
            ->get()
            ->map(function ($q) {
                return $q->id;
            })
            ->toArray();

        if (!empty($params['scopes'])) {
            if ($params['scopes '] == 'supervisor') {
                $personels = personel_with_child($personelId);
            }
        }

        return [
            "region" => Region::when(isset($params['region_name']), function ($q) use ($params) {
                $q->where('name', 'like', '%' . $params['region_name'] . '%');
            })
                ->select('id', 'name')
                ->where(function ($q) use ($personelId, $personels) {
                    $q->whereHas('subRegions', function ($q) use ($personels, $personelId) {
                        $q->whereIn('personel_id', $personels);
                    })->orWhereHas('subRegions.districts', function ($q) use ($personels, $personelId) {
                        $q->whereIn('personel_id', $personels);
                    })->orWhereIn('personel_id', $personels);
                })
                ->orderBy('name', 'asc')
                ->get(),

            "sub_region" => SubRegion::when(isset($params['sub_region_name']), function ($q) use ($params) {
                $q->where('name', 'like', '%' . $params['sub_region_name'] . '%');
            })
                ->when(isset($params['region_id']), function ($q) use ($params) {
                    $q->where('region_id', $params['region_id']);
                })
                ->select('id', 'name', 'region_id')
                ->where(function ($q) use ($personelId, $personels) {
                    $q->wherehas("region", function ($q) use ($personels, $personelId) {
                        $q->whereIn('personel_id', $personels);
                    })->orWhereHas('districts', function ($q) use ($personels, $personelId) {
                        $q->whereIn('personel_id', $personels);
                    })->orWhereIn('personel_id', $personels);
                })
                ->orderBy('name', 'asc')
                ->get(),

            "districts" => MarketingAreaDistrict::with("district")->when(isset($params['district_name']), function ($q) use ($params) {
                $q->whereHas("district", function ($q) use ($params) {
                    $q->where('name', 'like', '%' . $params['district_name'] . '%');
                });
            })
                ->when(isset($params['district_id']), function ($q) use ($params) {
                    $q->where('district_id', $params['district_id']);
                })
                ->when(isset($params['sub_region_id']), function ($q) use ($params) {
                    $q->where('sub_region_id', $params['sub_region_id']);
                })
                ->where(function ($q) use ($personelId, $personels) {
                    $q->whereHas('district', function ($q) use ($personels, $personelId) {
                        $q->whereIn('personel_id', $personels);
                    })->orWhereIn('personel_id', $personels);
                })
                ->get()->map(function ($q) {
                    return [
                        "id" => $q->id,
                        "district_id" => $q->district_id,
                        "name" => $q->district->name,
                        "personel_id" => $q->personel_id,
                        "sub_region_id" => $q->sub_region_id
                    ];
                }),
            "personels" => Personel::whereIn('id', $personels)->select('id', 'name', 'position_id')
                ->when(isset($params['personel_name']), function ($q) use ($params) {
                    $q->where('name', 'like', '%' . $params['personel_name'] . '%');
                })
                ->where('status', 1)
                ->with('position')->orderBy('name', 'asc')->get()->map(function ($q) {
                    return [
                        "id" => $q->id,
                        "name" => $q->name,
                        "position_name" => optional($q->position)->name ?? '-',
                    ];
                }),

            "grading" => DB::table('gradings')->when(isset($params['grading_name']), function ($q) use ($params) {
                $q->where('name', 'like', '%' . $params['grading_name'] . '%');
            })
                ->whereNull('deleted_at')
                ->orderBy('name', 'asc')
                ->select('id', 'name')
                ->get(),

            "agency" => AgencyLevel::when(isset($params['agency_name']), function ($q) use ($params) {
                $q->where('name', 'like', '%' . $params['agency_name'] . '%');
            })
                ->orderBy('name', 'asc')
                ->select('id', 'name')
                ->get(),
        ];
    }

    public function marketingList($request)
    {
        $query = PersonelSimple::query()->with([
            "regions" => function($q){
                return $q->select("name", "personel_id", "id");
            },
            "subRegions" => function($q){
                return $q->select("name", "personel_id", "id");
            }
        ]);

        $query->select(DB::raw("
            personels.id,
            personels.name,
            positions.position_name,
            personels.photo,
            personels.target,
            IFNULL(stores.total_store, 0) as total_store,

            IFNULL(core_farmers.core_farmer_count, 0) AS core_farmer_count,
            IFNULL(store_core_farmers_more_3.store_core_farmer_count, 0) AS store_core_farmer_count,
            
            IFNULL(sub_dealers.total_sub_dealer, 0) as total_sub_dealer,
            IFNULL(sub_dealers.total_subdealer_active, 0) as total_sub_dealer_active,
            IFNULL(sub_dealers.total_subdealer_closed, 0) as total_subdealer_closed,
            
            IFNULL(dealers.total_dealer, 0) as total_dealer,
            IFNULL(dealers.total_dealer_active, 0) as total_dealer_active,
            IFNULL(dealers.total_dealer_closed, 0) as total_dealer_closed,

            personels.status,
            personels.join_date,
            DATEDIFF(CURDATE(), personels.join_date) AS days_since_join,
            (IFNULL(sub_dealers.total_sub_dealer, 0) + IFNULL(dealers.total_dealer, 0)) as total_dealer_sub_dealer,
            (IFNULL(sub_dealers.total_subdealer_active, 0) + IFNULL(dealers.total_dealer_active, 0)) as total_dealer_sub_dealer_active,
            IFNULL(sales.total, 0) as sales_total_lifetime,
            IFNULL(sales.total_this_year, 0) as sales_total_this_year,
            IFNULL(sales.total_last_year, 0) as sales_total_last_year,
            IFNULL(sales.total_this_quarter, 0) as sales_total_this_quarter,
            IFNULL(sales.total_last_quarter, 0) as sales_total_last_quarter,
            (sales.total_this_quarter - sales.total_last_quarter ) / sales.total_last_quarter * 100  as percentage_sales_perquartal,
            (sales.total_this_year - sales.total_last_year) /  sales.total_last_year * 100  as percentage_sales,
            remaining_payment
        "));

        $query->leftJoin(DB::raw("(
                select
                    id,
                    name as position_name 
                from
                    positions
                where
                    deleted_at is NULL
            ) AS positions"), 
        function($q){
            $q->on("positions.id", "=", "personels.position_id");
        });

        //store
        $query->leftJoin(DB::raw("(
                select
                    personel_id,
                    count(id) as total_store
                from
                    stores
                where
                    deleted_at is NULL
                group by
                    personel_id
            ) AS stores"), 
        function($q){
            $q->on("stores.personel_id", "=", "personels.id");
        });

        //sub dealers
        $query->leftJoin(DB::raw("(
                select
                    personel_id,
                    count(id) as total_sub_dealer,
                    SUM(
                        CASE
                            WHEN sub_dealers.status = 'accepted' THEN 1
                            ELSE 0
                        END
                    ) AS total_subdealer_active,
                    SUM(
                        CASE
                            WHEN sub_dealers.closed_at is not null THEN 1
                            ELSE 0
                        END
                    ) AS total_subdealer_closed
                from
                    sub_dealers
                where
                    deleted_at is null
                group by
                    sub_dealers.personel_id
            ) AS sub_dealers"), 
        function($q){
            $q->on("sub_dealers.personel_id", "=", "personels.id");
        });

        //dealers
        $query->leftJoin(DB::raw("(
            select
                dealers.personel_id,
                count(id) as total_dealer,
                SUM(
                    CASE
                        WHEN dealers.status = 'accepted' THEN 1
                        ELSE 0
                    END
                ) AS total_dealer_active,
                SUM(
                    CASE
                        WHEN dealers.closed_at is not null THEN 1
                        ELSE 0
                    END
                ) AS total_dealer_closed
            from
                dealers
            where
                deleted_at is null
            group by
                dealers.personel_id
            ) AS dealers"), 
        function($q){
            $q->on("dealers.personel_id", "=", "personels.id");
        });

         //core farmer
        $query->leftJoin(DB::raw("(
                SELECT
                    stores.personel_id,
                    COUNT(core_farmers.id) AS core_farmer_count
                FROM
                    stores
                    LEFT JOIN core_farmers ON core_farmers.store_id = stores.id
                GROUP BY
                    stores.personel_id
            ) AS core_farmers"), 
        function($q){
            $q->on("core_farmers.personel_id", "=", "personels.id");
        });

        //core farmer more than 3 
        $query->leftJoin(DB::raw("(
                SELECT
                    stores.personel_id,
                    COUNT(core_farmers.id) AS store_core_farmer_count
                FROM
                    stores
                    LEFT JOIN core_farmers ON core_farmers.store_id = stores.id
                WHERE
                    stores.status IN ('transfered', 'accepted')
                GROUP BY
                    stores.personel_id
                HAVING
                    COUNT(core_farmers.id) > 3
            ) AS store_core_farmers_more_3"), 
        function($q){
            $q->on("store_core_farmers_more_3.personel_id", "=", "personels.id");
        });

        //sales
        $query->leftJoin(DB::raw("(
                SELECT
                    so.personel_id,
                    SUM(
                        (
                            (sd.quantity - IFNULL(sd.returned_quantity, 0)) * unit_price
                        ) - IFNULL(sd.discount, 0)
                    ) AS total,
                    SUM(
                        CASE
                            WHEN YEAR(so.inv_date) = YEAR(CURDATE()) THEN (
                                (sd.quantity - IFNULL(sd.returned_quantity, 0)) * unit_price
                            ) - IFNULL(sd.discount, 0)
                            ELSE 0
                        END
                    ) AS total_this_year,
                    SUM(
                        CASE
                            WHEN YEAR(so.inv_date) = YEAR(CURDATE()) - 1 THEN (
                                (sd.quantity - IFNULL(sd.returned_quantity, 0)) * unit_price
                            ) - IFNULL(sd.discount, 0)
                            ELSE 0
                        END
                    ) AS total_last_year,
                    SUM(
                        CASE
                            WHEN YEAR(so.inv_date) = YEAR(CURDATE())
                            AND QUARTER(so.inv_date) = QUARTER(CURDATE()) THEN (
                                (sd.quantity - IFNULL(sd.returned_quantity, 0)) * unit_price
                            ) - IFNULL(sd.discount, 0)
                            ELSE 0
                        END
                    ) AS total_this_quarter,
                    SUM(
                        CASE
                            WHEN YEAR(so.inv_date) = YEAR(CURDATE())
                            AND QUARTER(so.inv_date) = QUARTER(CURDATE()) - 1 THEN (
                                (sd.quantity - IFNULL(sd.returned_quantity, 0)) * unit_price
                            ) - IFNULL(sd.discount, 0)
                            WHEN YEAR(so.inv_date) = YEAR(CURDATE()) - 1
                            AND QUARTER(CURDATE()) = 1
                            AND QUARTER(so.inv_date) = 4 THEN (
                                (sd.quantity - IFNULL(sd.returned_quantity, 0)) * unit_price
                            ) - IFNULL(sd.discount, 0)
                            ELSE 0
                        END
                    ) AS total_last_quarter,
                    remaining_payment
                FROM
                    sales_order_details AS sd
                    JOIN products AS p ON p.id = sd.product_id
                    JOIN (
                        SELECT
                            s.`id`,
                            s.`order_number`,
                            s.`is_office`,
                            IF(ds.id = s.id, 0, s.type) `sales_to`,
                            s.`type`,
                            IFNULL(i.invoice, s.order_number) `inv`,
                            DATE(IFNULL(i.created_at, s.`date`)) `inv_date`,
                            s.`store_id`,
                            s.`model`,
                            s.`distributor_id`,
                            s.`personel_id`,
                            pay.remaining_payment
                        FROM
                            sales_orders AS s
                            LEFT JOIN invoices AS i ON i.deleted_at IS NULL
                            AND i.sales_order_id = s.id
                            LEFT JOIN (
                                SELECT
                                    invoice_id,
                                    sum(remaining_payment) as remaining_payment
                                FROM
                                    payments
                                where
                                    payments.deleted_at is NULL
                                group by
                                    payments.invoice_id
                            ) as pay on pay.invoice_id = i.id
                            LEFT JOIN (
                                SELECT
                                    o.id
                                FROM
                                    sales_orders AS o
                                    JOIN distributor_contracts AS d ON d.dealer_id = o.store_id
                                    AND o.model = 1
                                    LEFT JOIN invoices AS a ON a.sales_order_id = o.id
                                WHERE
                                    o.deleted_at IS NULL
                                    AND a.deleted_at IS NULL
                                    AND d.deleted_at IS NULL
                                    AND o.status IN ('confirmed', 'returned', 'pending')
                                    AND (
                                        o.`date` BETWEEN d.contract_start
                                        AND d.contract_end
                                        OR a.created_at BETWEEN d.contract_start
                                        AND contract_end
                                    )
                            ) AS ds ON ds.id = s.id
                        WHERE
                            s.deleted_at IS NULL
                            AND s.status IN ('confirmed', 'returned', 'pending')
                    ) AS so ON so.id = sd.sales_order_id
                WHERE
                    sd.deleted_at IS NULL
                    AND (so.sales_to IN (1, 2))
                GROUP BY
                    YEAR(so.inv_date),
                    so.personel_id
            ) as sales"), 
        function($q){
            $q->on("sales.personel_id", "=", "personels.id");
        });

        $query->groupBy("personels.id");

        //apply scopes
        if (isset($request['scopes'])) {
            foreach ($request['scopes'] as $scope) {
                if (isset($scope['name'])) {
                    $parameter = $scope['parameter'] ?? null;
                    if ($scope['name'] == "supervisor") {
                        $supervisorId = $parameter ?? auth()->user->personel_id;

                        $subordinates = DB::select("
                            WITH RECURSIVE Subordinates AS (
                                SELECT id, name, supervisor_id, 1 AS level
                                FROM personels
                                WHERE supervisor_id = :supervisorId
                                AND deleted_at is null

                                UNION ALL

                                SELECT p.id, p.name, p.supervisor_id, s.level + 1 AS level
                                FROM personels p
                                INNER JOIN Subordinates s ON p.supervisor_id = s.id
                            )
                            SELECT id
                            FROM Subordinates
                        ", ['supervisorId' => $supervisorId]);

                        $ids = array_map(function($subordinate) {
                            return $subordinate->id;
                        }, $subordinates);

                        $ids = array_column($subordinates, 'id');
                        array_push($ids, $parameter);
                        $query->whereIn("personels.id", $ids);
                    }
                }
            }
        }

        // Apply custom filters
        if ($request->has("filters")) {
            foreach ($request->filters as $filter) {
                if ($filter['field'] == "regions") {
                    $query->whereHas("regions", function($q) use($filter){
                        return $q->whereIn("id", $filter['value']);
                    });
                }elseif ($filter['field'] == "sub_regions") {
                    $query->whereHas("subRegions", function($q) use($filter){
                        return $q->whereIn("id", $filter['value']);
                    });
                }elseif ($filter['field'] == "districts") {
                    $query->whereHas("districts", function($q) use($filter){
                        return $q->whereIn("id", $filter['value']);
                    });
                }else{
                    if (isset($filter['field'], $filter['operator'], $filter['value'])) {
                        switch ($filter['operator']) {
                            case 'like':
                                $query->where($filter['field'], 'like', '%' . $filter['value'] . '%');
                                break;
                            case '=':
                            case '>':
                            case '<':
                            case '>=':
                            case '<=':
                                $query->where($filter['field'], $filter['operator'], $filter['value']);
                                break;
                            case 'in':
                                $query->whereIn($filter['field'], $filter['value']);
                                break;
                            case '!=':
                                $query->where($filter['field'], $filter['operator'], $filter['value']);
                                break;
                            default:
                                throw new \InvalidArgumentException('Invalid operator: ' . $filter['operator']);
                        }
                    }
                }
            }
        }

        if ($request->has("sort_by")) {
            $query->orderBy($request->sort_by['field'], $request->sort_by['direction']);
        }

        return $query->get();
    }
}
