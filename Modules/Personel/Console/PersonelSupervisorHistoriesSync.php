<?php

namespace Modules\Personel\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Personel\Entities\MarketingHierarchy;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelSupervisorHistory;

class PersonelSupervisorHistoriesSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'personel:syn-supervisor-histories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return 0;
        $quarter = now()->quarter;

        if (!$this->confirm('Sync personel supervisor current quarter?', false)) {
            $quarter = $this->anticipate('which quarter? (1-4)', [1, 2, 3, 4]);
        }

        MarketingHierarchy::query()
            ->whereRaw("quarter(marketing_hierarchies.from) = ?", $quarter)
            ->orderBy("marketing")
            ->get()
            ->sortBy([
                ["marketing", "asc"],
                ["from", "asc"],
            ])
            ->each(function ($personel) use ($quarter) {

                $positions = collect([
                    [
                        "level" => 1,
                        "position" => "marketing",
                        "marketing" => $personel->marketing,

                    ],
                    [
                        "level" => 2,
                        "position" => "rmc",
                        "marketing" => $personel->rmc,
                    ],
                    [
                        "level" => 3,
                        "position" => "ast_mdm",
                        "marketing" => $personel->ast_mdm,
                    ],
                    [
                        "level" => 4,
                        "position" => "mdm",
                        "marketing" => $personel->mdm,
                    ],
                    [
                        "level" => 5,
                        "position" => "mm",
                        "marketing" => $personel->mm,
                    ],
                ]);

                $positions
                    ->filter(fn($position) => $position["marketing"])
                    ->each(function ($position) use ($positions, $personel) {

                        $supervisor = $positions
                            ->reject(function ($supervisor_position) use ($position) {
                                return $supervisor_position["position"] == $position["position"];
                            })
                            ->filter(fn($supervisor_position) => $supervisor_position["marketing"])
                            ->filter(function ($supervisor_position) use ($position) {
                                return $position["marketing"] != $supervisor_position["marketing"];
                            })
                            ->filter(function ($supervisor_position) use ($position) {
                                return $supervisor_position["level"] > $position["level"];
                            })
                            ->each(function ($supervisor_position) use ($position) {
                                if (!$supervisor_position["marketing"]) {
                                    return true;
                                } elseif ($supervisor_position["marketing"]) {
                                    return false;
                                }
                            })
                            ->first();

                        if ($supervisor) {
                            if ($supervisor["marketing"]) {
                                $supervisor_marketing = Personel::where("name", $supervisor["marketing"])->first();
                                $marketing = Personel::where("name", $position["marketing"])->first();

                                if ($supervisor_marketing && $marketing) {

                                    /**
                                     * clear history bigger then hierarchy history date
                                     * we will assume this data was invalid
                                     */
                                    PersonelSupervisorHistory::query()
                                        ->where("personel_id", $marketing->id)
                                        ->where("supervisor_id", $supervisor_marketing->id)
                                        ->whereDate("change_at", ">=", $personel->from)
                                        ->delete();

                                    $history = PersonelSupervisorHistory::query()
                                        ->where("personel_id", $marketing->id)
                                        ->orderBy("change_at", "desc")
                                        ->first();

                                    /**
                                     * last history supervisor, if history supervisor match with
                                     * supervisot it's shold be, then we do not need to create
                                     * history again,
                                     */
                                    if ($history) {
                                        if ($history->supervisor_id != $supervisor_marketing->id) {
                                            PersonelSupervisorHistory::firstOrCreate([
                                                "personel_id" => $marketing->id,
                                                "position_id" => $marketing->position_id,
                                                "supervisor_id" => $supervisor_marketing->id,
                                                "change_at" => Carbon::parse($personel->from)->startOfDay(),
                                                "modified_by" => null,
                                                "note" => "sync",
                                            ]);
                                        }
                                    } 
                                    
                                    /**
                                     * ofcourse if marketing does not have history at all
                                     * we will create history for this marketintg
                                     * according hierarcy markting date
                                     */
                                    else {
                                        PersonelSupervisorHistory::firstOrCreate([
                                            "personel_id" => $marketing->id,
                                            "position_id" => $marketing->position_id,
                                            "supervisor_id" => $supervisor_marketing->id,
                                            "change_at" => Carbon::parse($personel->from)->startOfDay(),
                                            "modified_by" => null,
                                            "note" => "sync",
                                        ]);
                                    }
                                }
                            }
                        }

                        dump([
                            $position["position"] => $position["marketing"],
                            "supervisor" => is_array($supervisor) ? $supervisor["marketing"] : null,
                        ]);
                    });
            });

    }
}
