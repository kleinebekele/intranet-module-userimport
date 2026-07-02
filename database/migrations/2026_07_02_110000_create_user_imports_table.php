<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ein Eintrag pro Import-Lauf (wer, wann, welche Datei, wie viele Zeilen).
        Schema::create('user_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // wer hat importiert
            $table->string('filename');
            $table->string('status')->default('completed');       // completed | failed
            $table->unsignedInteger('total_rows')->default(0);    // Zeilen in der Datei
            $table->unsignedInteger('created_count')->default(0); // neu angelegte Benutzer
            $table->unsignedInteger('skipped_count')->default(0); // blockiert (E-Mail existierte) / ungültig
            $table->text('error_message')->nullable();            // falls der ganze Lauf scheitert
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_imports');
    }
};
