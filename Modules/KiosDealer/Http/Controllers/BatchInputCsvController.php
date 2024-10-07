<?php

namespace Modules\KiosDealer\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use ogrrd\CsvIterator\CsvIterator;
use Modules\DataAcuan\Entities\Bank;
use Modules\Address\Entities\Address;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\Dealer;
use Illuminate\Support\Facades\Validator;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\CoreFarmer;
use Modules\KiosDealer\Entities\DealerFile;

class BatchInputCsvController extends Controller
{
    use ResponseHandler;
    public function __construct(
        Store $store,
        CoreFarmer $core_farmer,
        Address $address,
        Dealer $dealer,
        SubDealer $sub_dealer) {

        $this->store = $store;
        $this->core_farmer = $core_farmer;
        $this->address = $address;
        $this->personel_id = !empty(auth()->user())?auth()->user()->personel_id :null;
        $this->dealer = $dealer;
        $this->sub_dealer = $sub_dealer;
    }
    

    public function Seeder(Request $request)
    {
        try {
            $data = null;
            if ($request->has("store")) {
                $data = $this->storeSeeder($request);
            } else if ($request->has("dealer")) {
                $data = $this->dealerSeeder($request);
            } else if ($request->has("sub_dealer")) {
                $data = $this->subDealerSeeder($request);
            }

            // return $data;
            return $this->response("00", "success", $data);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th->getMessage());
        }
    }

    /**
     * batch store store
     *
     * @param [type] $request
     * @return void
     */
    public function storeSeeder($request)
    {
        try {
            $pathToFile = $request->file;
            $delimiter = ';'; // optional
            $rows = new CsvIterator($pathToFile, $delimiter);
            $data = $rows->useFirstRowAsHeader();
            $seeded = [];
            foreach ($rows as $row) {
                // dd($row);
                $row = (object) $row;
                $personel_id = auth()->user()->personel_id;
                $store = $this->store->firstOrCreate([
                    'telephone' => $row->telephone,
                    'second_telephone' => $row->second_telephone,
                    'gmaps_link' => $row->gmaps_link,
                    'name' => $row->name,
                    'owner_name' => $row->owner_name,
                    'address' => $row->address,
                    'district_id' => $row->district_id,
                    'city_id' => $row->city_id,
                    'province_id' => $row->province_id,
                    'personel_id' => $personel_id,
                    'status' => "accepted",
                    'status_color' => "000000",
                ]);

                $core_farmer = $this->core_farmer->firstOrCreate([
                    'telephone' => $row->farmer_telephone,
                    'store_id' => $store->id,
                    'name' => $row->farmer_name,
                    'address' => $row->farmer_address,
                ]);
                array_push($seeded, $store);
            }
            return $seeded;
        } catch (\Throwable$th) {
            return $th->getMessage();
        }
    }

    /**
     * batch store dealer with csv
     *
     * @param [type] $request
     * @return void
     */
    public function dealerSeeder($request)
    {
        try {
            $pathToFile = $request->file;
            $delimiter = ';'; // optional
            $rows = new CsvIterator($pathToFile, $delimiter);
            $data = $rows->useFirstRowAsHeader();
            $seeded = [];
            foreach ($rows as $row) {
                // dump($row);
                $row = (object) $row;
                $status_fee = DB::table('status_fee')->where("name", $row->status_fee)->first();
                $grading = DB::table('gradings')->where("name", $row->grading)->first();
                $agency_level = DB::table('agency_levels')->where("name", $row->agency_level)->first();
                $entity = DB::table('entities')->where("name", $row->entitas)->first();
                $bank = $this->careatBank($row->bank_name);
                $owner_bank = $this->careatBank($row->owner_bank_name);
                $dealer = Dealer::firstOrCreate([
                    "personel_id" => $this->personel_id,
                    'dealer_id' => $row->dealer_id,
                    'prefix' => $row->prefix,
                    'name' => $row->name,
                    'sufix' => $row->sufix,
                    'telephone' => $row->telephone,
                    'second_telephone' => $row->second_telephone,
                    'owner_ktp' => $row->ktp,
                    'gmaps_link' => $row->gmaps_link,
                    'address' => $row->address,
                    'owner' => $row->owner,
                    'owner_address' => $row->owner_address,
                    'owner_telephone' => $row->owner_telephone,
                    'owner_npwp' => $row->npwp,
                    'agency_level_id' => $agency_level->id,
                    "email" => $row->email,
                    'entity_id' => $entity->id,
                    'status' => "accepted",
                    'status_color' => "000000",
                    'status_fee' => $status_fee->id,
                    'grading_id' => $grading->id,
                    "bank_account_number" => $row->bank_account_number,
                    "bank_id" => $bank->id,
                    "bank_account_name" => $row->bank_account_name,
                    "owner_bank_account_number" => $row->owner_bank_account_number,
                    "owner_bank_id" => $owner_bank->id,
                    "owner_bank_account_name" => $row->owner_bank_account_name,
                ], [
                    'last_grading' => Carbon::now(),
                ]);

                $address = $this->address->firstOrCreate([
                    "type" => "dealer",
                    "parent_id" => $dealer->id,
                    "province_id" => $row->province_id,
                    "city_id" => $row->city_id,
                    "district_id" => $row->district_id,
                ]);

                $address = $this->address->firstOrCreate([
                    "type" => "dealer_owner",
                    "parent_id" => $dealer->id,
                    "province_id" => $row->owner_province_id,
                    "city_id" => $row->owner_city_id,
                    "district_id" => $row->owner_district_id,
                ]);

                array_push($seeded, $dealer);
            }
            return $seeded;
        } catch (\Throwable$th) {
            return $th->getMessage();
        }
    }

    /**
     * create bank
     *
     * @param [type] $bank_name
     * @return void
     */
    public function careatBank($bank_name){
        $country_id = DB::table('countries')->where("label_en", "Indonesia, Republic of")->first()->id;
        $bank = Bank::firstOrCreate([
            "name" => $bank_name,
        ],[
            "country_id" => $country_id,
            "code" => "dummy"
        ]);

        return $bank;
    }

    /**
     * batch store sub dealer with csv
     *
     * @param [type] $request
     * @return void
     */
    public function subDealerSeeder($request)
    {
        try {
            $pathToFile = $request->file;
            $delimiter = ';'; // optional
            $rows = new CsvIterator($pathToFile, $delimiter);
            $data = $rows->useFirstRowAsHeader();
            $seeded = [];
            foreach ($rows as $row) {
                // dd($row);
                $row = (object) $row;
                $status_fee = DB::table('status_fee')->where("name", $row->status_fee)->first();
                $entity = DB::table('entities')->where("name", $row->entitas)->first();

                $sub_dealer = $this->sub_dealer->firstOrCreate([
                    "personel_id" => $this->personel_id,
                    'sub_dealer_id' => $row->sub_dealer_id,
                    'prefix' => $row->prefix,
                    'name' => $row->name,
                    'sufix' => $row->sufix,
                    'telephone' => $row->telephone,
                    'second_telephone' => $row->second_telephone,
                    'owner_ktp' => $row->ktp,
                    'gmaps_link' => $row->gmaps_link,
                    'address' => $row->address,
                    'owner' => $row->owner,
                    'owner_address' => $row->owner_address,
                    'owner_telephone' => $row->owner_telephone,
                    'owner_npwp' => $row->npwp,
                    "email" => $row->email,
                    'entity_id' => $entity->id,
                    'status' => "accepted",
                    'status_color' => "000000",
                    'status_fee' => $status_fee->id,
                ]);

                $address = $this->address->firstOrCreate([
                    "type" => "sub_dealer",
                    "parent_id" => $sub_dealer->id,
                    "province_id" => $row->province_id,
                    "city_id" => $row->city_id,
                    "district_id" => $row->district_id,
                ]);

                $owner_address = $this->address->firstOrCreate([
                    "type" => "sub_dealer_owner",
                    "parent_id" => $sub_dealer->id,
                    "province_id" => $row->owner_province_id,
                    "city_id" => $row->owner_city_id,
                    "district_id" => $row->owner_district_id,
                ]);

                array_push($seeded, $sub_dealer);
            }
            return $seeded;
        } catch (\Throwable$th) {
            return $th->getMessage();
        }
    }

    /**
     * dealer attachment seeder
     *
     * @param Request $request
     * @return void
     */
    public function dealerAttachment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "file" => "required"
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $file_type = $request->fileType;
            $file_extension = $file->getClientOriginalExtension();
            $file_name_splited = explode("_", $file->getClientOriginalName());
            $file_type = null;

            if (count($file_name_splited) == 2) {
                $dealers = $this->dealer->where("dealer_id", $file_name_splited[0])->get();
                $file_name = $file->getClientOriginalName();
                $file_type = explode(".", $file_name_splited[1]);
                $dealer_attachment = [];
                foreach ($dealers as $dealer) {
                    $file_name_on_s3 = $dealer->id . "-" . $file_type[0] . "." . $file_extension;
                    try {
                        $path = $request->file('file')->storeAs('public/dealer', $file_name_on_s3);
                        if ($path) {
                            $dealer_file = DealerFile::create([
                                "dealer_id" => $dealer->id,
                                "file_type" => $file_type[0],
                                "data" => $file_name_on_s3,
                            ]);

                            array_push($dealer_attachment, $dealer_file);
                        }
                        return $this->response("00", "succes to save dealer attachment", $dealer_attachment);;
                    } catch (\Exception$e) {
                        return $this->response("01", "failed to store attachment", $e->getMessage());
                    }
                }
            }
            else {
                return $this->response("01", "format data invalid", "format must dealerId_type.jpg example 1780_KTP.jpg");
            }
        }
    }
    
    /**
     * sub dealer attachment seeder
     *
     * @param Request $request
     * @return void
     */
    public function subDealerAttachment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "file" => "required"
        ]);

        if ($validator->fails()) {
            return $this->response("04", "invalid data send", $validator->errors());
        }
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $file_type = $request->fileType;
            $file_extension = $file->getClientOriginalExtension();
            $file_name_splited = explode("_", $file->getClientOriginalName());
            $file_type = null;

            if (count($file_name_splited) == 2) {
                $sub_dealers = $this->sub_dealer->where("sub_dealer_id", $file_name_splited[0])->get();
                $file_name = $file->getClientOriginalName();
                $file_type = explode(".", $file_name_splited[1]);
                $sub_dealer_attachment = [];
                foreach ($sub_dealers as $sub_dealer) {
                    $file_name_on_s3 = $sub_dealer->id . "-" . $file_type[0] . "." . $file_extension;
                    try {
                        $path = $request->file('file')->storeAs('public/dealer', $file_name_on_s3);
                        if ($path) {
                            $sub_dealer_file = DealerFile::create([
                                "dealer_id" => $sub_dealer->id,
                                "file_type" => $file_type[0],
                                "data" => $file_name_on_s3,
                            ]);

                            array_push($sub_dealer_attachment, $sub_dealer_file);
                        }
                        return $this->response("00", "succes to save dealer attachment", $sub_dealer_attachment);;
                    } catch (\Exception$e) {
                        return $this->response("01", "failed to store attachment", $e->getMessage());
                    }
                }
            }
            else {
                return $this->response("01", "format data invalid", "format must dealerId_type.jpg example 1780_KTP.jpg");
            }
        }
    }
}
