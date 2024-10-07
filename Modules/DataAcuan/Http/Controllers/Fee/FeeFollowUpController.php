<?php

namespace Modules\DataAcuan\Http\Controllers\Fee;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Modules\DataAcuan\Entities\FeeFollowUp;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Http\Requests\FeeFollowUpRequest;
use Modules\DataAcuan\Transformers\FeeFollowUpResource;
use Modules\DataAcuan\Transformers\FeeFollowUpCollectionResource;

class FeeFollowUpController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = FeeFollowUp::class;
    protected $request = FeeFollowUpRequest::class;
    protected $resource = FeeFollowUpResource::class;
    protected $collectionResource = FeeFollowUpCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
        ];
    }

    public function includes(): array
    {
        return [
        ];
    }
    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [
        ];
    }

    /**
     * filetr list
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "id",
            "follow_up_days",
            "fee",
            "settle_days",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            "id",
            "follow_up_days",
            "fee",
            "settle_days",
            "created_at",
            "updated_at",
        ];
    }

    /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "id",
            "follow_up_days",
            "fee",
            "settle_days",
            "created_at",
            "updated_at",
        ];
    }

    public function followUpDays(Request $request)
    {
        try {
            $response = FeeFollowUp::query();
            switch ($request->follow_up_days) {
                case 'smallest':
                    $response->orderBy('follow_up_days','asc');
                    break;

                case 'largest':
                    $response->orderBy('follow_up_days','desc');
                    break;
                
                default:
                    $response->orderBy('follow_up_days','asc');
                    break;
            }
            $response = $response->first();
            return $this->response("00", "success, fee follow up days", $response);
        } catch (\Throwable$th) {
            return $this->response("01", "failed, fee follow up days", $th->getMessage(), 500);
        }
    }

    public function deleteFeeFollowUp(Request $request, $id)
    {
        try {
            $fee_follow_up = FeeFollowUp::get();
            if (count($fee_follow_up) == 1) {
                return $this->response("01", "failed to delete fee follow up", "at least one data must exist");
            }
            $fee_follow_up = FeeFollowUp::findOrFail($id);
            $fee_follow_up->delete();
            return $this->response("00", "success, fee follow up deleted", $fee_follow_up);
        } catch (\Throwable$th) {
            return $this->response("01", "failed, fee follow up not deleted", $th->getMessage(), 500);
        }
    }
}
