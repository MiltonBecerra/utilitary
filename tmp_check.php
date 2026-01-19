<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Subscription;
$subs = Subscription::withTrashed()->where('user_id',1)->get();
foreach($subs as $s){echo $s->id, ' plan:', $s->plan_type, ' end:', $s->ends_at, ' deleted:', $s->deleted_at ? 'yes':'no', "\n";}
?>
