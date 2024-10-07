<?php

namespace Modules\Organisation\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Organisation\Entities\Entity;

class EntitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $entities  = [
            ['name' => 'Perseorangan'],
            ['name' => 'Pemerintah'],
            ['name' => 'Badan Hukum'],
        ];

        foreach($entities as $entity){
            Entity::create($entity);
        }
    }
}
