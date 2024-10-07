<?php

namespace Modules\Personel\Repositories\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\Region;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Division;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\SubRegion;

trait MandatoryProductAchievementRepository {

    public function mandatoryProductAchievement($params = [])
    {
        $filter = $this->filter($params);

        $select = " m.marketing_id, m.marketing, m.position, GROUP_CONCAT(DISTINCT ' ',ar.sub_region ORDER BY ar.sub_region ASC) AS sub_regions, pd.mandatory_target target_marketing, ROUND(SUM((sd.quantity - IFNULL(sd.returned_quantity,0)) * pd.volume)  / pd.mandatory_target * 100, 2) persentage_marketing,";
        $groupBy = "/* group_by: toko */ so.personel_id";
        $orderBy = "order by m.marketing, pd.mandatory_product";
        if (!empty($params['group_by']) && $params['group_by'] == "toko") {
            $select = " t.cust_id, t.store_id, t.toko_name, t.toko_owner, t.agency_level, ar.toko_lokasi,";
            $groupBy = "/* group_by: marketing */ t.store_id, t.model";
            $orderBy = "order by t.toko_name, ar.toko_lokasi";
        }

        if (!empty($params['sort']) && !empty($params['sort']['field'])) {
            $orderBy = " ORDER BY ". $params['sort']['field']." ".$params["sort"]['direction'];
        }

        $mainQuery = "select 
                YEAR(COALESCE(i.created_at, so.date)) sales_year,
                pd.product_group_id, pd.mandatory_product,
                ROUND(SUM((sd.quantity - IFNULL(sd.returned_quantity,0)) * pd.volume), 2) volume,
            /* custom select */
                $select 
                pd.metric_unit
            FROM sales_order_details as sd 
            /* JOIN SALES */
                JOIN sales_orders as so on so.id = sd.sales_order_id 
                    and so.status in ('confirmed','returned','pending') 
                    and so.deleted_at is null
                left JOIN invoices as i on i.sales_order_id = so.id
                    and i.deleted_at is null
                    and canceled_at is null
                left JOIN distributor_contracts as dc on dc.dealer_id = so.store_id
                    and so.model = 1 
                    and dc.deleted_at is null
                    and COALESCE(i.created_at, so.date) BETWEEN dc.contract_start
                    AND dc.contract_end

            /* JOIN PRODUCT MANDATORY */
                JOIN (
                    select pd.*, 
                        pg.id product_group_id, pg.name`mandatory_product`, 
                        pm.period_date`mandatory_year`, pm.target`mandatory_target`
                    from products as pd 
                    join product_group_members as pgm on pgm.product_id = pd.id 
                        and pgm.deleted_at is null 
                    join product_groups as pg on pg.id = pgm.product_group_id 
                        and pg.deleted_at is null 
                    join product_mandatories as pm on pm.product_group_id = pg.id 
                        and pm.deleted_at is null 
                    group by 
                        pd.id, pm.period_date
                ) as pd on pd.id = sd.product_id and pd.mandatory_year = YEAR(COALESCE(i.created_at, so.date))

            /* JOIN TOKO */
                JOIN (
                    select
                        tk.id store_id,
                        CONCAT('CUST-', dealer_id) cust_id,
                        1 model,
                        IF(
                            tk.name = '-'
                            or tk.name = null,
                            '',
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
                        ) toko_name,
                        CONCAT('(', tk.owner, ')') toko_owner,
                        al.name agency_level,
                        tk.grading_id,
                        date(tk.created_at) registerd_at,
                        personel_id toko_marketing_id
                    from
                        dealers as tk
                        JOIN agency_levels as al on al.id = tk.agency_level_id
                        and al.deleted_at is null
                    where
                        tk.deleted_at is null
                    UNION
                    select
                        id store_id,
                        CONCAT('CUST-SUB-', sub_dealer_id) cust_id,
                        2 model,
                        IF(
                            tk.name = '-'
                            or tk.name = null,
                            '',
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
                        ) toko_name,
                        CONCAT('(', tk.owner, ')') toko_owner,
                        null agency_level,
                        tk.grading_id,
                        date(tk.created_at) toko_registed_at,
                        personel_id toko_marketing_id
                    from
                        sub_dealers as tk
                    where
                        tk.deleted_at is null
                ) as t on t.store_id = so.store_id and t.model = so.model 
            /* JOIN MARKETING AREA */
                JOIN (
                    select
                        ad.parent_id as store_id,
                        IF(ad.type = 'dealer', 1, 2) model,
                        area.*, 
                        id.name as district,
                        ic.name as city,
                        ip.name as province,
                        CONCAT_WS(', ', id.name, ic.name, ip.name) as toko_lokasi
                    from
                        address_with_details as ad
                        LEFT JOIN (
                            select
                                md.district_id,
                                ms.id as sub_region_id,
                                ms.name as sub_region,
                                mr.id as region_id,
                                mr.name as region
                            from
                                marketing_area_districts as md
                                JOIN marketing_area_sub_regions as ms on ms.id = md.sub_region_id
                                and ms.deleted_at is null
                                JOIN marketing_area_regions as mr on mr.id = ms.region_id
                                and mr.deleted_at is null
                            Where
                                md.deleted_at is null
                        ) as area on area.district_id = ad.district_id
                        JOIN indonesia_districts as id on id.id = ad.district_id
                        JOIN indonesia_cities as ic on ic.id = ad.city_id
                        JOIN indonesia_provinces as ip on ip.id = ad.province_id
                    where
                        ad.deleted_at is null
                        and ad.type IN ('dealer', 'sub_dealer')
                    group by
                        parent_id
                ) ar on ar.store_id = so.store_id and ar.model = so.model

            /* JOIN PERSONELS */
                JOIN (
                    select
                        ps.id marketing_id,
                        ps.name marketing,
                        rl.name position
                    from
                        personels as ps
                        join positions as rl on rl.id = ps.position_id
                        and rl.deleted_at is null
                    where
                        ps.deleted_at is null
                ) as m on m.marketing_id = so.personel_id
            /* WHERE STATEMENT */
                where sd.deleted_at is null and dc.id is null 
                -- tahun dipilih --
                    and YEAR(COALESCE(i.created_at,so.date)) = $filter[year] 
                $filter[product_group_filter]

                $filter[region_filter]
                
                $filter[sub_region_filter]
                
                $filter[marketing_filter]
            /* GROUP BY STATEMENT */ 
                group by
                    pd.product_group_id,
                    $groupBy
            /* ORDER BY STATEMENT*/
                    $orderBy
        ";

        if (!empty($params['group_by']) && $params['group_by'] == "toko") {
            return DB::select(DB::raw($mainQuery));
        }

        $query  = "select 
            sales_year, 
            marketing_id, 
            marketing,  
            position,  
            sub_regions as group_rmc,  
            JSON_ARRAYAGG(
                JSON_OBJECT(
                    'product_group_id', product_group_id,
                    'mandatory_product', mandatory_product,
                    'volume', volume,
                    'metric_unit', metric_unit,
                    'target_marketing', target_marketing,
                    'persentage_marketing', persentage_marketing
                )
            ) AS data,
            COUNT(*) AS total_data
            FROM ($mainQuery) as product 
            group by marketing_id
            $orderBy";
    
        return DB::select(DB::raw($query));
    }

    private function filter($params)
    {
        $year = date('Y');
        if (!empty($params['year'])) {
            $year = $params['year'];
        }

        $productGroupIdFilter = '';
        if (!empty($params['product_group_id'])) {
            $productGroupId = "'" . implode("','", $params['product_group_id']) . "'";
            $productGroupIdFilter = "  and pd.product_group_id IN ($productGroupId) ";
        }

        $marketingFilter = '';
        if (!empty($params['marketing_id'])) {
            $marketingId = "'" . implode("','", $params['marketing_id']) . "'";
            $marketingFilter = " and m.marketing_id in ($marketingId) ";
        }

        $regionFilter = '';
        if (!empty($params['region_id'])) {
            $regionId = "'" . implode("','", $params['region_id']) . "'";
            $regionFilter = " and ( ar.region_id IN ($regionId) or ar.region_id is null )";
        }

        $subRegionFilter = '';
        if (!empty($params['sub_region_id'])) {
            $subRegionId = "'" . implode("','", $params['sub_region_id']) . "'";
            $subRegionFilter = " and ( ar.sub_region_id IN ($subRegionId) or ar.sub_region_id is null )";
        }

        return [
            "year" => $year,
            "product_group_filter" => $productGroupIdFilter,
            "region_filter" => $regionFilter,
            "sub_region_filter" => $subRegionFilter,
            "marketing_filter" => $marketingFilter
        ];
    }

    public function dashboardMandatoryProduct($productGroupId, $personelId, $year, $month)
    {
        return DB::select(DB::raw("select
                YEAR(COALESCE(i.created_at, so.date)) sales_year,
                pd.product_group_id,
                pd.mandatory_product,
                ROUND(
                    SUM(
                        (sd.quantity - IFNULL(sd.returned_quantity, 0)) * pd.volume
                    ),
                    2
                ) volume
            FROM
                sales_order_details as sd
                /* JOIN SALES */
                JOIN sales_orders as so on so.id = sd.sales_order_id and so.status in ('confirmed','returned','pending') and so.deleted_at is null
                left JOIN invoices as i on i.sales_order_id = so.id
                and i.deleted_at is null
                and canceled_at is null
                left JOIN distributor_contracts as dc on dc.dealer_id = so.store_id
                and so.model = 1
                and dc.deleted_at is null
                and COALESCE(i.created_at, so.date) BETWEEN dc.contract_start
                AND dc.contract_end
                /* JOIN PRODUCT MANDATORY */
                JOIN (
                    select
                        pd.*,
                        pg.id product_group_id,
                        pg.name `mandatory_product`,
                        pm.period_date `mandatory_year`,
                        pm.target `mandatory_target`
                    from
                        products as pd
                        join product_group_members as pgm on pgm.product_id = pd.id
                        and pgm.deleted_at is null
                        join product_groups as pg on pg.id = pgm.product_group_id
                        and pg.deleted_at is null
                        join product_mandatories as pm on pm.product_group_id = pg.id
                        and pm.deleted_at is null
                    group by
                        pd.id,
                        pm.period_date
                ) as pd on pd.id = sd.product_id
                and pd.mandatory_year = YEAR(COALESCE(i.created_at, so.date))
                /* JOIN TOKO */
                JOIN (
                    select
                        tk.id store_id,
                        CONCAT('CUST-', dealer_id) cust_id,
                        1 model,
                        IF(
                            tk.name = '-'
                            or tk.name = null,
                            '',
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
                        ) toko_name,
                        CONCAT('(', tk.owner, ')') toko_owner,
                        al.name agency_level,
                        tk.grading_id,
                        date(tk.created_at) registerd_at,
                        personel_id toko_marketing_id
                    from
                        dealers as tk
                        JOIN agency_levels as al on al.id = tk.agency_level_id
                        and al.deleted_at is null
                    where
                        tk.deleted_at is null
                    UNION
                    select
                        id store_id,
                        CONCAT('CUST-SUB-', sub_dealer_id) cust_id,
                        2 model,
                        IF(
                            tk.name = '-'
                            or tk.name = null,
                            '',
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
                        ) toko_name,
                        CONCAT('(', tk.owner, ')') toko_owner,
                        null agency_level,
                        tk.grading_id,
                        date(tk.created_at) toko_registed_at,
                        personel_id toko_marketing_id
                    from
                        sub_dealers as tk
                    where
                        tk.deleted_at is null
                ) as t on t.store_id = so.store_id
                and t.model = so.model
                /* JOIN MARKETING AREA */
                JOIN (
                    select
                        ad.parent_id as store_id,
                        IF(ad.type = 'dealer', 1, 2) model,
                        area.*,
                        id.name as district,
                        ic.name as city,
                        ip.name as province,
                        CONCAT_WS(', ', id.name, ic.name, ip.name) as toko_lokasi
                    from
                        address_with_details as ad
                        LEFT JOIN (
                            select
                                md.district_id,
                                ms.id as sub_region_id,
                                ms.name as sub_region,
                                mr.id as region_id,
                                mr.name as region
                            from
                                marketing_area_districts as md
                                JOIN marketing_area_sub_regions as ms on ms.id = md.sub_region_id
                                and ms.deleted_at is null
                                JOIN marketing_area_regions as mr on mr.id = ms.region_id
                                and mr.deleted_at is null
                            Where
                                md.deleted_at is null
                        ) as area on area.district_id = ad.district_id
                        JOIN indonesia_districts as id on id.id = ad.district_id
                        JOIN indonesia_cities as ic on ic.id = ad.city_id
                        JOIN indonesia_provinces as ip on ip.id = ad.province_id
                    where
                        ad.deleted_at is null
                        and ad.type IN ('dealer', 'sub_dealer')
                    group by
                        parent_id
                ) ar on ar.store_id = so.store_id
                and ar.model = so.model
            --     /* JOIN PERSONELS */
                JOIN (
                    select
                        ps.id marketing_id,
                        ps.name marketing,
                        rl.name position
                    from
                        personels as ps
                        join positions as rl on rl.id = ps.position_id
                        and rl.deleted_at is null
                    where
                        ps.deleted_at is null
                ) as m on m.marketing_id = so.personel_id
                /* WHERE STATEMENT */
            where
                sd.deleted_at is null
                and dc.id is null -- tahun dipilih --
                and YEAR(COALESCE(i.created_at, so.date)) = $year
                and month(COALESCE(i.created_at, so.date)) in ($month)
                and m.marketing_id in ($personelId)
                and pd.product_group_id = '$productGroupId'
                /* GROUP BY STATEMENT */
            group by
                pd.product_group_id,
                /* group_by: toko */
                so.personel_id
                /* ORDER BY STATEMENT*/
            ORDER BY
                volume asc"
                )
            );
    }

}