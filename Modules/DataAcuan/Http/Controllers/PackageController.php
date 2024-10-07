<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Exports\PackageExport;
use App\Traits\ResponseHandler;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Http\Requests\PackageOnUpdateRequest;
use Modules\DataAcuan\Http\Requests\PackageRequest;

class PackageController extends Controller
{
    use ResponseHandler;

    public function __construct(Package $package, Product $product)
    {
        $this->package = $package;
        $this->product = $product;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        try {
            $packages = $this->package->query()
                ->where('packaging', $request->packaging)
                ->orderBy("packaging")
                ->get();
            return $this->response('00', 'packages index', $packages);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to get packages index', $th->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('dataacuan::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(PackageRequest $request)
    {
        try {
            $package = $this->package->firstOrCreate([
                "product_id" => $request->product_id,
                "packaging" => $request->packaging,
                "quantity_per_package" => $request->quantity_per_package,
                "unit" => $request->unit,
                "weight" => $request->weight,
            ]);

            if ($request->isActive == '1') {
                $this->package->query()
                    ->where("product_id", $request->product_id)
                    ->update([
                        "isActive" => "0",
                    ]);
            }
            $package = $this->package->findOrFail($package->id);
            $package->isActive = $request->isActive;
            $package->save();
            return $this->response('00', 'packaging saved', $package);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to save packaging', $th->getMessage());
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        try {
            $package = $this->package->query()
                ->with('product')
                ->where('id', $id)
                ->first();
            return $this->response('00', 'packaging detail', $package);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to display packaging detail', $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('dataacuan::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(PackageOnUpdateRequest $request, $id)
    {
        try {
            if ($request->isActive == '1') {
                $this->package->query()
                    ->where("product_id", $request->product_id)
                    ->update([
                        "isActive" => "0",
                    ]);
            }

            $package = $this->package->where('id', $id)
                ->update($request->all());
            $package = $this->package->query()
                ->with('product')
                ->where('id', $id)
                ->first();
            $package->isActive = $request->isActive;
            $package->save();

            return $this->response('00', 'packaging updated', $package);
        } catch (\Throwable$th) {
            return $this->response('01', 'failed to update packaging', $th->getMessage());
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
            $package = $this->package->findOrFail($id);
            $package_check = $this->package->query()
                ->where("id", $id)
                ->whereHas("salesOrderDetail")
                ->with("salesOrderDetail")
                ->get();
            // if (count($package_check) !== 0) {
            //     return $this->response('01', 'failed to delete package', "package was use in another data");
            // } else {

                $package->delete();
                return $this->response('00', 'packaging deleted', $package);
            // }

        } catch (\Throwable$th) {
            return $this->response('01', 'failed to delete packaging', $th->getMessage());
        }
    }

    public function export()
    {
        try {
            $data = (new PackageExport)->store('packages.xlsx', 's3');
            return $this->response("00", "export succes", $data);
        } catch (\Throwable$th) {
            return $this->response("00", "export succes", $th->getMessage());
        }
    }
}
