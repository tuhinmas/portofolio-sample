<?php

namespace Modules\Organisation\Http\Controllers;

use App\Models\Address;
use App\Models\Contact;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Organisation\Entities\Organisation;
use Modules\Organisation\Http\Requests\OrganisationRequest;
use Modules\Personel\Entities\Personel;

class OrganisationController extends Controller
{
    public function __construct(Organisation $organisation, Contact $contact, Address $address, Personel $personel)
    {
        $this->organisation = $organisation;
        $this->contact = $contact;
        $this->address = $address;
        $this->personel = $personel;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        try {
            $organisations = $this->organisation->query()
                ->with("category", "bussiness_sector", "contact", "entity", "address", "holding")
                ->where("status", "1")
                ->orderBy("name")
                ->get();
            return $this->response('00', 'Organisation index', $organisations);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display organisation', $th);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('organisation::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(OrganisationRequest $request)
    {
        try {
            $organisation = $this->organisation->firstOrCreate([
                'prefix' => $request->awalan,
                'name' => $request->name,
                'sufix' => $request->akhiran,
                'npwp' => $request->npwp,
            ], [
                'note' => $request->note,
                'holding_id' => $request->holding_id,
                'entity_id' => $request->entity_id,
                'chart' => $request->chart,
            ]);

            // $organisation_category = $this->unset_category($request);
            $organisation->category()->sync($request->category);

            return $this->response('00', 'Organisation saved', $organisation);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to save organisation', $th->getMessage());
        }
    }

    /**
     * unset category with null value
     *
     * @param [type] $request
     * @return void
     */
    private static function unset_category($request)
    {
        $organisation_category = [
            $request->supplier,
            $request->dealer,
            $request->vendor,
            $request->financial,
            $request->internal,
        ];
        foreach ($organisation_category as $key => $organisation_category_1) {
            if ($organisation_category_1 == "") {
                unset($organisation_category[$key]);
            }
        }

        return $organisation_category;
    }

    /**
     * save organisation chart
     *
     * @return void
     */
    private static function organisation_chart($request, $organisation)
    {
        if ($request->hasFile('chart')) {
            $image = $request->file('chart');
            $image_extension = $image->getClientOriginalExtension();
            $image_name = $organisation->id . '.' . $image_extension;
            $image_folder = '/image/chart/';
            $image_location = $image_folder . $image_name;
            try {
                $image->move(public_path($image_folder), $image_name);
                $organisation->chart = $image_location;
                $organisation->save();
            } catch (\Exception$e) {
                return response()->json([
                    'response_code' => '01',
                    'response_msg' => 'Chart gagal di tambahakan',
                    'data' => $e,
                ], 500);
            }
        }

        return $organisation;
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $organisation = $this->organisation->findOrFail($id);
            $organisation = $this->organisation->query()
                ->where("id", $id)
                ->with("category", "bussiness_sector", "contact", "entity", "address", "holding")
                ->first();
            return $this->response('00', 'organisaion detail', $organisation);
        } catch (\Throwable$th) {
            return $this->response('00', 'Failed to display organisation detail', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('organisation::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $organisation = $this->organisation->findOrFail($id);

            if ($request->status != null || $request->status != "") {
                $organisation->status = $request->status;
            } else {
                $organisation->prefix = $request->awalan;
                $organisation->name = $request->name;
                $organisation->sufix = $request->akhiran;
                $organisation->npwp = $request->npwp;
                $organisation->note = $request->note;
                $organisation->holding_id = $request->holding_id;
                $organisation->entity_id = $request->entity_id;
                $organisation->chart = $request->chart;
                $organisation->status = $request->has('status') ? $request->status : $organisation->status;
            }
            $organisation->save();

            // $organisation_category = $this->unset_category($request);
            $organisation->category()->sync($request->category);

            return $this->response('00', 'Organisation updated', $organisation);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to update organisation', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $organisation = $this->organisation->findOrFail($id);
            $contact = $this->contact->where('parent_id', $id)->delete();
            $address = $this->address->where('parent_id', $id)->delete();
            $personel = $this->personel->where('organisation_id', $id)->update(['organisation_id' => null]);
            $organisation->delete();
            return $this->response('00', 'Organisation deleted', $organisation);
        } catch (\Throwable$th) {
            return $this->response('01', 'Failed to delete organisation', $th->getMessage());
        }
    }

    public function allOrganisation(Request $request)
    {
        try {
            if (!$request->status) {
                $request->status = ["1", "0"];
            }
            $organisations = $this->organisation->query()
                ->with("category", "bussiness_sector", "contact", "entity", "address", "holding")
                ->where('name', 'like', '%' . $request->name . '%')
                ->whereIn('status', $request->status)
                ->orderBy("status", "desc")
                ->orderBy("name")
                ->paginate($request->limit ? $request->limit : 15);
            return $this->response('00', 'Organisation list', $organisations);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display organisations', $th->getMessage());
        }
    }

    /**
     * response
     *
     * @param [type] $code
     * @param [type] $message
     * @param [type] $data
     * @return void
     */
    public function response($code, $message, $data)
    {
        return response()->json([
            'response_code' => $code,
            'response_message' => $message,
            'data' => $data,
        ]);
    }
}
