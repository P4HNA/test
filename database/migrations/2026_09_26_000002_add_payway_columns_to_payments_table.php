<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| ABA PayWay KHQR replaces the Bakong integration, and the two gateways hand
| back different things:
|
|  - Bakong gave us an md5 of the QR payload, which was the lookup key for
|    every status check. PayWay has no such value -- it looks a payment up by
|    tran_id, which is our own invoice_no. So md5 becomes nullable; legacy
|    Bakong rows keep theirs and stay checkable through payments:confirm.
|  - PayWay returns a rendered KHQR image (base64 PNG) and an ABA Mobile
|    deeplink. Storing the image means the payment page needs no QR-drawing
|    library from a CDN, and the deeplink is what lets a phone jump straight
|    into ABA Mobile instead of scanning its own screen.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Base64 PNG from PayWay ("qr_image_template" => template3_color).
            // longText: a KHQR PNG base64 runs well past TEXT's 64KB on MySQL.
            $table->longText('qr_image')->nullable()->after('qr');
            // abapay_deeplink -- opens ABA Mobile on the payer's phone.
            $table->text('deeplink')->nullable()->after('qr_image');
        });

        // Separate statement: SQLite rebuilds the table to change a column, so
        // it must not collide with the additions above.
        Schema::table('payments', function (Blueprint $table) {
            $table->string('md5', 32)->nullable()->change();
        });
    }

    public function down(): void
    {
        // PayWay rows have no md5, so the old NOT NULL shape cannot hold them.
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['qr_image', 'deeplink']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('md5', 32)->nullable(false)->default('')->change();
        });
    }
};
