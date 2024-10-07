<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\DealerBenefit;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\PaymentMethod;

class DealerBenefitTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        DB::table('dealer_benefits')->delete();
        $platinum = Grading::where("name", "Platinum")->pluck("id")->first();
        $gold = Grading::where("name", "Gold")->pluck("id")->first();
        $silver = Grading::where("name", "Silver")->pluck("id")->first();
        $hijau = Grading::where("name", "Hijau")->pluck("id")->first();
        $kuning = Grading::where("name", "Kuning")->pluck("id")->first();
        $merah = Grading::where("name", "Merah")->pluck("id")->first();
        $orange = Grading::where("name", "Orange")->pluck("id")->first();
        $putih = Grading::where("name", "Putih")->pluck("id")->first();
        $pink = Grading::where("name", "Pink")->pluck("id")->first();
        $payment = PaymentMethod::all();
        $gradings = Grading::where("name", "!=", "Hitam")->where("name", "!=", "Platinum")->where("name", "!=", "Gold")->where("name", "!=", "Silver")->where("name", "!=", "Pink")->get();
        $agency_levels = AgencyLevel::where("name", "!=", "R3")->get();
        $product_category_a = DB::table('product_categories')->whereNull("deleted_at")->where("name", "a")->first()->id;
        $product_category_b = DB::table('product_categories')->whereNull("deleted_at")->where("name", "b")->first()->id;
        $product_category_special = DB::table('product_categories')->whereNull("deleted_at")->where("name", "special")->first()->id;
        foreach ($gradings as $grading) {
            DealerBenefit::create([
                "grading_id" => $grading->id,
                "payment_method_id" => $payment->where("name", "Cash")->first()->id,
                "agency_level_id" => $agency_levels->pluck("id")->all(),
                "benefit_discount" => [
                    [
                        "stage" => 1,
                        "type" => "always",
                        "product_category" => [
                            $product_category_a,
                        ],
                        "discount" => [
                            (object) [
                                "minimum_order" => 0,
                                "discount" => 2.5,
                                "maximum_discount" => 0,
                            ],
                        ],
                    ],
                ],
            ]);
        }

        /**
         * benefit grade platinum
         */
        DealerBenefit::create([
            "grading_id" => $platinum,
            "payment_method_id" => $payment->where("name", "Cash")->first()->id,
            "agency_level_id" => $agency_levels->pluck("id")->all(),
            "benefit_discount" => [
                [
                    "stage" => 1,
                    "type" => "always",
                    "product_category" => [
                        $product_category_a,
                    ],
                    "discount" => [
                        (object) [
                            "minimum_order" => 0,
                            "discount" => 2.5,
                            "maximum_discount" => 0,
                        ],
                    ],
                ],
                [
                    "stage" => 2,
                    "type" => "threshold",
                    "product_category" => [
                        $product_category_a,
                    ],
                    "discount" => [
                        (object) [
                            "minimum_order" => 500000000,
                            "discount" => 1.5,
                            "maximum_discount" => 0,
                        ],
                        (object) [
                            "minimum_order" => 750000000,
                            "discount" => 2,
                            "maximum_discount" => 0,
                        ],
                    ],
                ],
            ],
        ]);
        
        /**
         * benefit grade dold
         */
        DealerBenefit::create([
            "grading_id" => $gold,
            "payment_method_id" => $payment->where("name", "Cash")->pluck("id")->first(),
            "agency_level_id" => $agency_levels->pluck("id")->all(),
            "benefit_discount" => [
                [
                    "stage" => 1,
                    "type" => "always",
                    "product_category" => [
                        $product_category_a,
                    ],
                    "discount" => [
                        (object) [
                            "minimum_order" => 0,
                            "discount" => 2.5,
                            "maximum_discount" => 0,
                        ],
                    ],
                ],
                [
                    "stage" => 2,
                    "type" => "threshold",
                    "product_category" => [
                        $product_category_a,
                    ],
                    "discount" => [
                        (object) [
                            "minimum_order" => 300000000,
                            "discount" => 1,
                            "maximum_discount" => 0,
                        ]
                    ],
                ],
            ],
        ]);
        
        /**
         * benefit grade silver
         */
        DealerBenefit::create([
            "grading_id" => $silver,
            "payment_method_id" => $payment->where("name", "Cash")->first()->id,
            "agency_level_id" => $agency_levels->pluck("id")->all(),
            "benefit_discount" => [
                [
                    "stage" => 1,
                    "type" => "always",
                    "product_category" => [
                        $product_category_a,
                    ],
                    "discount" => [
                        (object) [
                            "minimum_order" => 0,
                            "discount" => 2.5,
                            "maximum_discount" => 0,
                        ],
                    ],
                ],
                [
                    "stage" => 2,
                    "type" => "threshold",
                    "product_category" => [
                        $product_category_a,
                    ],
                    "discount" => [
                        (object) [
                            "minimum_order" => 300000000,
                            "discount" => 0.5,
                            "maximum_discount" => 0,
                        ]
                    ],
                ],
            ],
        ]);

        if ($pink) {
            DealerBenefit::create([
                "grading_id" => $pink,
                "payment_method_id" => $payment->where("name", "Cash")->first()->id,
                "agency_level_id" => $agency_levels->pluck("id")->all(),
                "benefit_discount" => [
                    [
                        "stage" => 1,
                        "type" => "always",
                        "product_category" => [
                            $product_category_a,
                        ],
                        "discount" => [
                            (object) [
                                "minimum_order" => 0,
                                "discount" => 2.5,
                                "maximum_discount" => 0,
                            ],
                        ],
                    ],
                    [
                        "stage" => 2,
                        "type" => "threshold",
                        "product_category" => [
                            $product_category_a,
                        ],
                        "discount" => [
                            (object) [
                                "minimum_order" => 500000000,
                                "discount" => 1.5,
                                "maximum_discount" => 0,
                            ],
                            (object) [
                                "minimum_order" => 750000000,
                                "discount" => 2,
                                "maximum_discount" => 0,
                            ],
                            (object) [
                                "minimum_order" => 1000000000,
                                "discount" => 3,
                                "maximum_discount" => 0,
                            ],
                        ],
                    ],
                    [
                        "stage" => 3,
                        "type" => "threshold",
                        "product_category" => [
                            $product_category_a,
                        ],
                        "discount" => [
                            (object) [
                                "minimum_order" => 500000000,
                                "discount" => 2,
                                "maximum_discount" => 0,
                            ],
                            (object) [
                                "minimum_order" => 750000000,
                                "discount" => 3,
                                "maximum_discount" => 0,
                            ],
                            (object) [
                                "minimum_order" => 1000000000,
                                "discount" => 4,
                                "maximum_discount" => 0,
                            ],
                        ],
                    ],
                ],
            ]); 
        }


        /**
         * pending code
         */
        // DealerBenefit::create([
        //     "grading_id" => $platinum,
        //     "payment_method_id" => $payment->where("name", "Cash")->pluck("id")->first(),
        //     "agency_level_id" => $agency_levels->pluck("id")->all(),
        //     "benefit_discount" => [
        //         "stage_1" => [
        //             "type" => "always",
        //             "discount" =>
        //             [
        //                 "discount" => 2.5,
        //             ],
        //         ],
        //         "stage_2" => [
        //             "type" => "threshold",
        //             "discount" => [
        //                 "product_category" => "a",
        //                 "minimum_order" => 750000000,
        //                 "discount" => 2,
        //             ],
        //         ],
        //         "stage_3" => [
        //             "type" => "threshold",
        //             "discount" => [
        //                 "product_category" => "a",
        //                 "minimum_order" => 500000000,
        //                 "discount" => 1.5,
        //             ],
        //         ],
        //     ],
        // ]);
        // DealerBenefit::create([
        //     "grading_id" => $gold,
        //     "payment_method_id" => $payment->where("name", "Cash")->pluck("id")->first(),
        //     "agency_level_id" => $agency_levels->pluck("id")->all(),
        //     "benefit_discount" => [
        //         "stage_1" => [
        //             "type" => "always",
        //             "discount" =>
        //             [
        //                 "discount" => 2.5,
        //             ],
        //         ],
        //         "stage_2" => [
        //             "type" => "threshold",
        //             "discount" => [
        //                 "product_category" => "a",
        //                 "minimum_order" => 300000000,
        //                 "discount" => 1,
        //             ],
        //         ],
        //     ],
        // ]);
        // DealerBenefit::create([
        //     "grading_id" => $silver,
        //     "payment_method_id" => $payment->where("name", "Cash")->pluck("id")->first(),
        //     "agency_level_id" => $agency_levels->pluck("id")->all(),
        //     "benefit_discount" => [
        //         "stage_1" => [
        //             "type" => "always",
        //             "discount" =>
        //             [
        //                 "discount" => 2.5,
        //             ],
        //         ],
        //         "stage_2" => [
        //             "type" => "threshold",
        //             "discount" => [
        //                 "product_category" => "a",
        //                 "minimum_order" => 300000000,
        //                 "discount" => 0.5,
        //             ],
        //         ],
        //     ],
        // ]);

    }
}
