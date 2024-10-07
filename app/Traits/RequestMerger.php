<?php

namespace App\Traits;

/**
 *
 */
trait RequestMerger
{
    public function includeRequestMerger($include)
    {
        $includes = explode(",", $include);
        $relation_include = collect();
        $includes = collect($includes)->each(function ($include) use (&$relation_include) {
            $relation_include->push([
                "relation" => $include,
            ]);
        });

        return $relation_include->toArray();
    }

    public function includesRequestFormatter($request, $with = [])
    {
        if ($request->has("includes")) {
            $includes = collect($request->includes)
                ->map(function ($include) {
                    if (collect($include)->has("relation")) {
                        return $include["relation"];
                    }
                });
                
            $new_includes = collect($with)->merge($includes)->unique()->toArray();
            return $new_includes;
        }
    }
}
