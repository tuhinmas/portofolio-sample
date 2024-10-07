<?php

namespace Modules\Personel\Console\History;

use Illuminate\Console\Command;
use Modules\Personel\Entities\Personel;
use ogrrd\CsvIterator\CsvIterator;

class MarketingStatusHistoryFixingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'marketing:status_history_fixing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'there are broken data whic join date same with resign date.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected Personel $personel
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
        $pathToFile = 'Modules/Personel/Database/Seeders/csv/LIST MARKETING OUT.csv';
        $delimiter = ','; // optional
        $rows = new CsvIterator($pathToFile, $delimiter);
        $data = $rows->useFirstRowAsHeader();
        $nomor = 1;
        foreach ($rows as $row) {
            $marketing = $this->personel->query()
                ->where("name", $row["marketing"])
                ->where("join_date", $row["join_date"])
                // ->whereColumn("join_date", "=", "resign_date")
                ->where("status", "3")
                ->first();

            if (!$marketing) {
                $this->info("<fg=red>marketing not found</>");
                $this->info($row["marketing"]);
                continue;
            }

            if ($marketing->join_date ==  $row["resign_date"]) {
                $this->info("<fg=red>join date invalid</>");

            }

            $marketing->resign_date = $row["resign_date"];
            $marketing->save();

            dump([
                "nomor" => $nomor,
                "marketing" => $marketing->name,
            ]);
            $nomor++;
        }
    }

}
