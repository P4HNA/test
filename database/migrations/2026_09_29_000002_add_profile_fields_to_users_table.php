<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| The details a student fills in about themselves, as opposed to the details
| the account needs to work (name, email, phone). All optional: a profile with
| none of them filled in still works, it just scores lower on the completeness
| meter on /my-profile.
|
| avatar_path is a key on the 'public' disk, the same convention as
| courses.thumbnail_url -- see ProfileController::storeAvatar().
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('headline', 120)->nullable()->after('avatar_path');
            $table->text('bio')->nullable()->after('headline');
            $table->string('location', 120)->nullable()->after('bio');
            $table->string('website')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'headline', 'bio', 'location', 'website']);
        });
    }
};
