<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Promotions: a discount or an announcement an admin runs for a while.
|
| A discount really lowers the price charged at checkout (see
| Course::salePrice() and PayWayVerifier::qrFor()). An announcement is a
| message only. Either kind can be shown in the bar at the top of the homepage.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->default('discount');            // discount | announcement
            $table->string('title_en', 120);
            $table->string('title_km', 120)->nullable();
            $table->string('message_en', 240)->nullable();
            $table->string('message_km', 240)->nullable();
            $table->unsignedTinyInteger('discount_percent')->nullable(); // 1-90, discounts only
            $table->string('applies_to', 20)->default('all');           // all | course | category
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('category', 64)->nullable();
            $table->dateTime('starts_at')->nullable();                  // empty = right away
            $table->dateTime('ends_at')->nullable();                    // empty = until turned off
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(true);
            $table->string('cta_label', 40)->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
