<?php

namespace Modules\Organisation\Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Organisation\Entities\Entity;
use Modules\Organisation\Entities\Organisation;

class OrganisationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $entity = Entity::inRandomOrder()->first();
        $contact = Contact::inRandomOrder()->first();
        $organisations = [
            [
                'name' => 'Javamas',
                'npwp' => '123123123',
                'note' => 'javamas adalah',
                'holding_id' => null ,
                'entity_id' => $entity->id,
            ],
            [
                'name' => 'KumpulKebon',
                'npwp' => '000111222333',
                'note' => 'Kumpul kebon adalah',
                'holding_id' => null ,
                'entity_id' => $entity->id,
            ],
        ];

        foreach($organisations as $organisation){
            Organisation::create($organisation);
        }
    }
}
