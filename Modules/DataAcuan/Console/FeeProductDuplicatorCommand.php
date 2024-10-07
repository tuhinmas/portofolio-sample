<?php

namespace Modules\DataAcuan\Console;

use Illuminate\Console\Command;
use Modules\DataAcuan\Entities\Fee;

class FeeProductDuplicatorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'fee:copy_fee_product_from_to';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'duplicate fee product referenece from one quarter to another.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        Fee $fee_product
    ) {
        $this->fee_product = $fee_product;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $year_from = now()->format("Y");
        $quarter_from = now()->quarter;
        $product_id = null;

        $year_to = now()->format("Y");
        $quarter_to = now()->quarter;
        if (!$this->confirm('Copy fee product from this year?', true)) {
            $year_from = $this->choice(
                'which year?',
                [
                    now()->subYear(1)->format("Y"),
                    now()->subYear(2)->format("Y"),
                    now()->subYear(3)->format("Y"),
                ],
            );
        }

        $quarter_from = $this->anticipate('which quarter? (1-4)', [1, 2, 3, 4]);

        /* copy to */
        if (!$this->confirm('Copy fee product to this year?', true)) {
            $year_to = $this->choice(
                'which year?',
                [
                    now()->addYears(1)->format("Y"),
                    now()->addYears(2)->format("Y"),
                    now()->addYears(3)->format("Y"),
                ],
            );
        }

        $quarter_to = $this->anticipate('which quarter? (1-4)', [1, 2, 3, 4]);

        
        /* copy to */
        if ($this->confirm('one product only?', true)) {
            $product_id = $this->ask('which product ID?');
        }

        $nomor = 1;
        $fee_products = $this->fee_product->query()
            ->where("year", $year_from)
            ->where("quartal", $quarter_from)
            ->when($product_id, function($QQQ)use($product_id){
                return $QQQ->where("product_id", $product_id);
            })
            ->get()

        /* clean up existing fee product references */
            ->groupBy("product_id")
            ->each(function ($fee_per_product, $product_id) use ($year_to, $quarter_to) {

                $this->fee_product->query()
                    ->where("product_id", $product_id)
                    ->where("year", $year_to)
                    ->where("quartal", $quarter_to)
                    ->get()
                    ->each(function ($fee_product) {
                        $fee_product->delete();
                    });
            })
            ->flatten()
            ->sortBy([
                ["product_id", "asc"],
                ["type", "asc"],
                ["quantity", "asc"],
            ])

        /* add new fee product */
            ->each(function ($fee_product) use ($year_to, $quarter_to, &$nomor) {
                $fee_product = $this->fee_product->firstOrcreate([
                    "year" => $year_to,
                    "quartal" => $quarter_to,
                    "type" => $fee_product->type,
                    "product_id" => $fee_product->product_id,
                    "quantity" => $fee_product->quantity,
                    "fee" => $fee_product->fee,
                ]);

                $fee_product->nomor = $nomor;
                dump(collect($fee_product)->only(["year", "quartal", "type", "product_id", "quantity", "fee"])->toArray());
                $nomor++;
            });
    }
}
