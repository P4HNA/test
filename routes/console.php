<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| The PayWay safety net.
|
| ABA PayWay pushes rather than being polled: it POSTs to /payway/callback the
| moment a payment lands, and that is what unlocks the course. This sweeper exists
| only for the case where that callback never arrives -- a tunnel down, a deploy
| mid-request, a dropped retry -- AND the customer has closed the payment page.
| Their money has moved and their course is locked, and nothing else in the system
| would ever find out.
|
| Ten minutes is chosen to be quiet: the callback handles the normal case within
| seconds, and a swept payment is by definition one nobody is sitting waiting for.
| Each run asks about at most 60 pending QRs from the last 24 hours, so the cost
| stays flat however busy the shop gets.
|
| This needs the scheduler to actually be running on the server:
|     * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
| Without it the sweeper never fires and you are back to relying on the callback
| alone.
*/
Schedule::command('payway:recheck --hours=24 --limit=60')
    ->everyTenMinutes()
    // A slow PayWay must not stack runs on top of each other.
    ->withoutOverlapping(10)
    ->runInBackground();
