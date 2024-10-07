<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\Position;
use Modules\Personel\Entities\Personel;

class MarketingSupervisorTableSeeder extends Seeder
{
    public function __construct(Personel $personel, Position $position)
    {
        $this->personel = $personel;
        $this->position = $position;
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->personel->query()
            ->where('name', 'Ilzam Nuzuli')
            ->update(['supervisor_id' => $this->personel->where('name', 'Moh Syaefudin Zuhri')->first()->id]);
        $this->personel->query()
            ->where('name', 'Trisno Aji')
            ->update(['supervisor_id' => $this->personel->where('name', 'Moh Syaefudin Zuhri')->first()->id]);
        $this->personel->query()
            ->where('name', 'Wendri Muji atmoko')
            ->update(['supervisor_id' => $this->personel->where('name', 'Moh Syaefudin Zuhri')->first()->id]);
        $this->personel->query()
            ->where('name', 'Arista wahyudiyanto')
            ->update(['supervisor_id' => $this->personel->where('name', 'Moh Syaefudin Zuhri')->first()->id]);

        $this->personel->query()
            ->where('name', 'Budianto')
            ->update(['supervisor_id' => $this->personel->where('name', 'Alid Hermawan')->first()->id]);
        $this->personel->query()
            ->where('name', 'Alid Hermawan')
            ->update(['supervisor_id' => $this->personel->where('name', 'Andi Fianto')->first()->id]);

        $this->personel->query()
            ->where('name', 'Moh Syaefudin Zuhri')
            ->update(['supervisor_id' => $this->personel->where('name', 'Budi Kartika')->first()->id]);
        $this->personel->query()
            ->where('name', 'Andi Fianto')
            ->update(['supervisor_id' => $this->personel->where('name', 'Budi Kartika')->first()->id]);
    }
}
