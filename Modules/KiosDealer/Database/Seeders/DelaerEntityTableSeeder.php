<?php

namespace Modules\KiosDealer\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\Entity;
use Modules\KiosDealer\Entities\Dealer;

class DelaerEntityTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $entity = Entity::where("name", "Perseorangan")->first();
        $dealers = Dealer::where("entity_id", null)
            ->update([
                "entity_id" => $entity->id,
            ]);
    }
}
