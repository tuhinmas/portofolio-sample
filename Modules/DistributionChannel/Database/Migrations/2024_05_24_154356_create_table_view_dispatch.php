<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
        create VIEW view_dispatch_list AS (
            select
                *
            from
                (
                    select
                        `discpatch_order`.`id`,
                        `discpatch_order`.`id_armada`,
                        `discpatch_order`.`delivery_address_id`,
                        `discpatch_order`.`type_driver`,
                        `discpatch_order`.`transportation_type`,
                        `discpatch_order`.`armada_identity_number`,
                        `discpatch_order`.`date_delivery`,
                        `discpatch_order`.`driver_name`,
                        `discpatch_order`.`invoice_id`,
                        `discpatch_order`.`is_active`,
                        `discpatch_order`.`promotion_good_request_id`,
                        `discpatch_order`.`driver_phone_number`,
                        `discpatch_order`.`receipt_id`,
                        `discpatch_order`.`created_at`,
                        `discpatch_order`.`updated_at`,
                        `discpatch_order`.`deleted_at`,
                        `discpatch_order`.`id_warehouse`,
                        `discpatch_order`.`dispatch_order_weight`,
                        `discpatch_order`.`dispatch_order_number`,
                        `discpatch_order`.`order_number`,
                        `discpatch_order`.`dispatch_note`,
                        `discpatch_order`.`status`,
                        'dispatch_order' as dispatch_type
                    from
                        `discpatch_order`
                    where
                        invoice_id is not null
                        and `deleted_at` is null
                    UNION
                    select
                        `dispatch_promotions`.`id`,
                        `dispatch_promotions`.`id_armada`,
                        `dispatch_promotions`.`delivery_address_id`,
                        `dispatch_promotions`.`type_driver`,
                        `dispatch_promotions`.`transportation_type`,
                        `dispatch_promotions`.`armada_identity_number`,
                        `dispatch_promotions`.`date_delivery`,
                        `dispatch_promotions`.`driver_name`,
                        NULL as invoice_id,
                        `dispatch_promotions`.`is_active`,
                        `dispatch_promotions`.`promotion_good_request_id`,
                        `dispatch_promotions`.`driver_phone_number`,
                        `dispatch_promotions`.`receipt_id`,
                        `dispatch_promotions`.`created_at`,
                        `dispatch_promotions`.`updated_at`,
                        `dispatch_promotions`.`deleted_at`,
                        `dispatch_promotions`.`id_warehouse`,
                        `dispatch_promotions`.`dispatch_order_weight`,
                        `dispatch_promotions`.`dispatch_order_number`,
                        `dispatch_promotions`.`order_number`,
                        `dispatch_promotions`.`dispatch_note`,
                        `dispatch_promotions`.`status`,
                        'dispatch_promotion' as dispatch_type
                    from
                        `dispatch_promotions`
                    where
                        `deleted_at` is null
                ) as dispatch_list
            )
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS view_dispatch_list");
    }
};
