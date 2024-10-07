<?php

namespace Modules\KiosDealer\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\Address;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\SubDealerTemp;

class SubDealerAddressDoentHaveAddressCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'dealer:fix_address_if_doesnt_have';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'if was confirm but has no address, get from history';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected SubDealerTemp $sub_dealer_temp,
        protected SubDealer $sub_dealer,
        protected Address $address,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * sub dealer dosnt have address
         */
        $this->sub_dealer->query()
            ->with([
                "adressDetail",
            ])
            ->whereDoesntHave("adressDetail")
            ->get()
            ->each(function ($sub_dealer) {
                $sub_dealer_temp = DB::table('sub_dealer_temps')
                    ->where(function ($QQQ) use ($sub_dealer) {
                        return $QQQ
                            ->where("status", "submission of changes")
                            ->where("sub_dealer_id", $sub_dealer->id);
                    })
                    ->orWhere(function ($QQQ) use ($sub_dealer) {
                        return $QQQ
                            ->where("status", "filed")
                            ->where("personel_id", $sub_dealer->personel_id)
                            ->where("distributor_id", $sub_dealer->distributor_id)
                            ->where("prefix", $sub_dealer->prefix)
                            ->where("name", $sub_dealer->name)
                            ->where("sufix", $sub_dealer->sufix)
                            ->where("address", $sub_dealer->address)
                            ->where("telephone", $sub_dealer->telephone)
                            ->where("second_telephone", $sub_dealer->second_telephone)
                            ->where("latitude", $sub_dealer->latitude)
                            ->where("longitude", $sub_dealer->longitude)
                            ->where("owner", $sub_dealer->owner)
                            ->where("owner_address", $sub_dealer->owner_address)
                            ->where("owner_ktp", $sub_dealer->owner_ktp)
                            ->where("owner_npwp", $sub_dealer->owner_npwp)
                            ->where("owner_telephone", $sub_dealer->owner_telephone)
                            ->where("email", $sub_dealer->email)
                            ->where("entity_id", $sub_dealer->entity_id)
                            ->where("note", $sub_dealer->note);
                    })
                    ->orderByDesc("updated_at")
                    ->first();

                if (!$sub_dealer_temp) {
                    return;
                }

                $sub_dealer_address = DB::table('address_with_detail_temps')
                    ->whereNull("deleted_at")
                    ->where("parent_id", $sub_dealer_temp->id)
                    ->where("type", "sub_dealer")
                    ->first();

                $sub_dealer_owner_address = DB::table('address_with_detail_temps')
                    ->whereNull("deleted_at")
                    ->where("parent_id", $sub_dealer_temp->id)
                    ->where("type", "sub_dealer_owner")
                    ->first();

                $this->address->firstOrCreate([
                    "type" => $sub_dealer_address->type,
                    "parent_id" => $sub_dealer->id,
                ], [
                    "province_id" => $sub_dealer_address->province_id,
                    "city_id" => $sub_dealer_address->city_id,
                    "district_id" => $sub_dealer_address->district_id,
                ]);

                $address = $this->address->firstOrCreate([
                    "type" => $sub_dealer_address->type,
                    "parent_id" => $sub_dealer->id,
                ], [
                    "province_id" => $sub_dealer_address->province_id,
                    "city_id" => $sub_dealer_address->city_id,
                    "district_id" => $sub_dealer_address->district_id,
                ]);

                dump([
                    $address->parent_id,
                ]);
            });

        /* sub dealer has address but more than 2 */
        $this->sub_dealer->query()
            ->with([
                "adressDetail",
            ])
            ->has("adressDetail", ">", 2)
            ->get()
            ->each(function ($sub_dealer) {

                $address = $sub_dealer->adressDetail;
                $sub_dealer_address = $sub_dealer
                    ->adressDetail
                    ->sortByDesc("updated_at")
                    ->filter(fn($address) => $address->type == "sub_dealer")
                    ->first();

                $sub_dealer_owner_address = $sub_dealer
                    ->adressDetail
                    ->sortByDesc("updated_at")
                    ->filter(fn($address) => $address->type == "sub_dealer_owner")
                    ->first();

                $address
                    ->reject(function ($address) use ($sub_dealer_address, $sub_dealer_owner_address) {
                        return in_array($address->id, [$sub_dealer_address?->id, $sub_dealer_owner_address?->id]);
                    })
                    ->each(function ($address) {
                        dump($address->delete());
                    });
            });

        /* sub dealer temps has address but more than 2 */
        $this->sub_dealer_temp->query()
            ->with([
                "adressDetail",
            ])
            ->has("adressDetail", ">", 2)
            ->get()
            ->each(function ($sub_dealer_temp) {

                $address = $sub_dealer_temp->adressDetail;
                $sub_dealer_address = $sub_dealer_temp
                    ->adressDetail
                    ->sortByDesc("updated_at")
                    ->filter(fn($address) => $address->type == "sub_dealer")
                    ->first();

                $sub_dealer_owner_address = $sub_dealer_temp
                    ->adressDetail
                    ->sortByDesc("updated_at")
                    ->filter(fn($address) => $address->type == "sub_dealer_owner")
                    ->first();

                $address
                    ->reject(function ($address) use ($sub_dealer_address, $sub_dealer_owner_address) {
                        return in_array($address->id, [$sub_dealer_address?->id, $sub_dealer_owner_address?->id]);
                    })
                    ->each(function ($address) {
                        dump($address->delete());
                    });
            });

        dd("ok");
    }
}
