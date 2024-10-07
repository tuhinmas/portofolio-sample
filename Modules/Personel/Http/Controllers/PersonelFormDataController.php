<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PersonelFormDataController extends Controller
{
    use ResponseHandlerV2;

    public function __invoke($personel_id)
    {
        try {
            $personel = DB::table('personels as p')
                ->leftJoin("personels as spv", function ($join) {
                    $join
                        ->on("p.supervisor_id", "spv.id")
                        ->whereNull("p.deleted_at");
                })
                ->leftJoin("identity_cards as idk", function ($join) {
                    $join
                        ->on("idk.id", "p.identity_card_type")
                        ->whereNull("p.deleted_at");
                })
                ->join("positions as po", "po.id", "p.position_id")
                ->join("religions as rel", "rel.id", "p.religion_id")
                ->join("organisations as org", "org.id", "p.organisation_id")
                ->join("countries as citizenship", "citizenship.id", "p.citizenship")
                ->where("p.id", $personel_id)
                ->whereNull("p.deleted_at")
                ->whereNull("po.deleted_at")
                ->whereNull("rel.deleted_at")
                ->whereNull("org.deleted_at")
                ->whereNull("citizenship.deleted_at")

                ->select("p.*",
                    "spv.name as supervisor_name",
                    "po.name as position_name",
                    "org.name as organisation_name",
                    "idk.name as identity_card_type",
                    "rel.name as religion_name",
                    "citizenship.id as citizenship_id",
                    "citizenship.label_en as citizenship_name",
                )
                ->first();

            $personel_address = DB::table('addresses as adr')
                ->whereNull("adr.deleted_at")
                ->join("indonesia_districts as ids", "ids.id", "adr.district_id")
                ->join("indonesia_cities as idc", "idc.id", "adr.city_id")
                ->join("indonesia_provinces as idp", "idp.id", "adr.province_id")
                ->where("adr.parent_id", $personel_id)
                ->select("adr.*", "ids.name as district", "idc.name as city", "idp.name as province")
                ->get()
                ->map(function ($address) {
                    $address->address = $address->detail_address
                    . ", " . Str::title($address->district)
                    . ", " . Str::title($address->city)
                    . ", " . Str::title($address->province)
                    . ", " . Str::title($address->post_code);
                    return collect($address)->only(["id", "type", "address", "gmaps_link"]);
                });

            $personel_banks = DB::table('bank_personels as pb')
                ->join("banks as b", "b.id", "pb.bank_id")
                ->where("pb.personel_id", $personel_id)
                ->whereNull("pb.deleted_at")
                ->whereNull("b.deleted_at")
                ->select("pb.id", "pb.owner", "pb.rek_number", "b.name")
                ->get();

            $personel_contacts = DB::table('contacts as c')
                ->where("c.parent_id", $personel_id)
                ->whereNull("c.deleted_at")
                ->select("c.id", "c.contact_type", "c.data")
                ->get();

            $personel_detail = [
                "detail" => $personel,
                "address" => $personel_address,
                "banks" => $personel_banks,
                "contacts" => $personel_contacts,
            ];
            return $this->response("00", "success", $personel_detail);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }

}
