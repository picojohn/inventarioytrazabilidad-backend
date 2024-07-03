<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\PedidoSeeder;
use Database\Seeders\StartupSeeder;
use Database\Seeders\RemisionSeeder;
use Database\Seeders\SeguridadSeeder;
use Database\Seeders\ContenedorSeeder;
use Database\Seeders\InventarioSeeder;
use Database\Seeders\NewOptionsSeeder;
use Database\Seeders\ParametrosSeeder;
use Database\Seeders\OperacionesSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(StartupSeeder::class);
        $this->call(SeguridadSeeder::class);
        $this->call(ParametrosSeeder::class);
        $this->call(PedidoSeeder::class);
        $this->call(InventarioSeeder::class);
        $this->call(RemisionSeeder::class);
        $this->call(ContenedorSeeder::class);
        $this->call(OperacionesSeeder::class);
        $this->call(NewOptionsSeeder::class);
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
