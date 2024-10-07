<?php

namespace Modules\Personel\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class PersonelDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call(IdentityCardTableSeeder::class);
        // $this->call(PersonelTableSeeder::class);
        $this->call(NewMarketingV3TableSeeder::class);
        $this->call(MarketingKiosV4TableSeeder::class);
        $this->call(MarketingSupervisorTableSeeder::class);
        $this->call(PersonelAndUserTableSeeder::class);
        $this->call(PersonelDCTableSeeder::class);
        $this->call(PersonelFinalApprovalEventTableSeeder::class);
        // $this->call(PersonelUserAcccountTableSeeder::class);
        $this->call(ScAndOmRolePersmissionTableSeeder::class);
    }
}
