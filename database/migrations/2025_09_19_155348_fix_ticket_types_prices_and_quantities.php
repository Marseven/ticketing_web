<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Corriger les prix et quantités des ticket_types
        
        // Événement 1: Festival Électro Gabonais 2025
        DB::table('ticket_types')->where('id', 1)->where('name', 'Early Bird')->update([
            'price' => 12000.00,
            'available_quantity' => 2000
        ]);
        
        DB::table('ticket_types')->where('id', 2)->where('name', 'VIP')->update([
            'price' => 35000.00,
            'available_quantity' => 500
        ]);
        
        // Événement 2: Concert Afrobeat Live
        DB::table('ticket_types')->where('id', 3)->where('name', 'Standard')->update([
            'price' => 10000.00,
            'available_quantity' => 1500
        ]);
        
        DB::table('ticket_types')->where('id', 4)->where('name', 'VIP')->update([
            'price' => 25000.00,
            'available_quantity' => 300
        ]);
        
        // Événement 3: Théâtre Les Misérables
        DB::table('ticket_types')->where('id', 5)->where('name', 'Orchestre')->update([
            'price' => 12000.00,
            'available_quantity' => 200
        ]);
        
        DB::table('ticket_types')->where('id', 6)->where('name', 'Balcon')->update([
            'price' => 18000.00,
            'available_quantity' => 150
        ]);
        
        // Événement 4: Conférence Tech Innovation 2025
        DB::table('ticket_types')->where('id', 7)->where('name', 'Standard')->update([
            'price' => 25000.00,
            'available_quantity' => 500
        ]);
        
        DB::table('ticket_types')->where('id', 8)->where('name', 'Premium')->update([
            'price' => 45000.00,
            'available_quantity' => 200
        ]);
        
        // Événement 5: Festival des Arts Urbains Libreville
        DB::table('ticket_types')->where('id', 9)->where('name', 'Pass Journée')->update([
            'price' => 8000.00,
            'available_quantity' => 2500
        ]);
        
        DB::table('ticket_types')->where('id', 10)->where('name', 'Pass Artiste')->update([
            'price' => 20000.00,
            'available_quantity' => 200
        ]);
        
        // Événement 6: Match de Gala - Lions vs Panthères
        DB::table('ticket_types')->where('id', 11)->where('name', 'Tribune populaire')->update([
            'price' => 5000.00,
            'available_quantity' => 8000
        ]);
        
        DB::table('ticket_types')->where('id', 12)->where('name', 'Tribune VIP')->update([
            'price' => 20000.00,
            'available_quantity' => 1000
        ]);
        
        // Événement 7: Festival de Jazz du Golfe de Guinée
        DB::table('ticket_types')->where('id', 13)->where('name', 'Pass 1 jour')->update([
            'price' => 15000.00,
            'available_quantity' => 400
        ]);
        
        DB::table('ticket_types')->where('id', 14)->where('name', 'Pass 3 jours')->update([
            'price' => 35000.00,
            'available_quantity' => 300
        ]);
        
        DB::table('ticket_types')->where('id', 15)->where('name', 'VIP 3 jours')->update([
            'price' => 75000.00,
            'available_quantity' => 100
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre les valeurs à zéro (état précédent)
        DB::table('ticket_types')->whereIn('id', [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15])->update([
            'price' => 0.00,
            'available_quantity' => null
        ]);
    }
};
