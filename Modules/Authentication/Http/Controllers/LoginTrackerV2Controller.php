<?php

namespace Modules\Authentication\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Entities\Device;
use Modules\Authentication\Entities\UserAccessHistory;
use Modules\Personel\Entities\Personel;

class LoginTrackerV2Controller extends Controller
{
    use ResponseHandlerV2;
    
    public function __invoke(Request $request)
    {
        try {
            $query = UserAccessHistory::query();

            $subQuery = DB::table('user_access_histories')
                ->select('user_id', DB::raw('MAX(created_at) as max_created_at'))
                ->whereNull('deleted_at')
                ->groupBy('user_id');

            $query->join(DB::raw("({$subQuery->toSql()}) as max_uah"), function ($join) use ($subQuery) {
                $join->on('user_access_histories.user_id', '=', 'max_uah.user_id')
                    ->on('user_access_histories.created_at', '=', 'max_uah.max_created_at');
            })
            ->mergeBindings($subQuery); // Merge the bindings from the subquery

            $query->join("users", "users.id", "=", "user_access_histories.user_id")->whereNull("user_access_histories.deleted_at");
            $query->join("personels", "personels.id", "=", "users.personel_id")->whereNull("personels.deleted_at");
            $query->leftJoin("positions", "positions.id", "=", "personels.position_id")->whereNull("positions.deleted_at");

            $query->select(
                "user_access_histories.id",
                "user_access_histories.latitude",
                "user_access_histories.longitude",
                "personels.id as personel_id",
                "personels.name as personel_name",
                "positions.id as position_id",
                "positions.name as position_name",
                "user_access_histories.created_at as last_history",
                DB::raw('DATE(user_access_histories.created_at) as date_created_at')
            );

            if ($request->has("scopes")) {
                foreach ($request->scopes as $key => $value) {
                    switch ($value['name']) {
                        case 'supervisor':
                            $personel = supervisor_personels($value['parameter']);
                            $query->whereIn("personel_id", $personel);
                            break;
                    }
                }
            }

            if ($request->has("scope_supervisor")) {
                $personel = supervisor_personels(auth()->user()->personel_id);
                $query->whereIn("personel_id", $personel);
            }

            if ($request->has("login_today")) {
                foreach ($request->filters as $key => $value) {
                    if ($value['field'] != "user_access_histories.created_at") {
                        $query->whereDate("user_access_histories.created_at", now()->format("Y-m-d"));
                    }
                }
            }

            if ($request->has("scopeMarketingUnderSupervisor")) {
                    $personel_id = $request->personel_id ?? auth()->user()->personel_id;
                    $personels_id = [$personel_id];
                    $personels = Personel::with("children")->where('id', $personel_id)->first();
                    foreach (($personels->children ?? []) as $level1) { //mdm
                        $personels_id[] = $level1->id;
                    }
                $query->whereIn("personel_id", $personels_id);
            }


            // Apply custom filters
            if ($request->has("filters")) {
                foreach ($request->filters as $filter) {
                    if (isset($filter['field'], $filter['operator'], $filter['value'])) {
                        switch ($filter['operator']) {
                            case 'like':
                                $query->where($filter['field'], 'like', '%' . $filter['value'] . '%');
                                break;
                            case '=':
                                $query->where($filter['field'], $filter['operator'], $filter['value']);
                                break;
                            case '>':
                            case '<':
                            case '>=':
                            case '<=':
                                $query->whereDate($filter['field'], $filter['operator'], $filter['value']);
                                break;
                            case 'in':
                                $query->whereIn($filter['field'], $filter['value']);
                                break;
                            case '!=':
                                $query->where($filter['field'], $filter['operator'], $filter['value']);
                                break;
                            default:
                                throw new \InvalidArgumentException('Invalid operator: ' . $filter['operator']);
                        }
                    }
                }
            }

            // Apply sorting
            if ($request->has("sort_by")) {
                $sortDirection = $request->sort_direction ?? 'asc';
                $query->orderBy($request->sort_by, $sortDirection);
            } else {
                $query->orderBy("personels.name", "asc")->orderBy("user_access_histories.created_at", "desc");
            }

            // Ambil hasil query
            $response = $query->get();
            
            // Jika ada parameter limit, gunakan pagination
            if (isset($params["limit"])) {
                $response = $query->paginate($params["limit"]);
            }

            return $this->response("00", "succes", $response);
        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}
