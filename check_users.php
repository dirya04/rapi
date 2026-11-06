<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Event;
use App\Models\Complaint;

echo "USERS:\n";
foreach (User::all() as $u) {
    echo "ID: {$u->id} | {$u->username} | Email: " . ($u->email ?? 'NULL') . " | Phone: {$u->phone_number}\n";
}

echo "\nEVENTS TODAY (" . date('Y-m-d') . "):\n";
foreach (Event::whereDate('date', date('Y-m-d'))->get() as $e) {
    echo "Event ID: {$e->id} | Name: {$e->event_name} | Status: {$e->status} | Start: {$e->start_time} | End: {$e->end_time}\n";
}

echo "\nALL EVENTS:\n";
foreach (Event::all() as $e) {
    echo "Event ID: {$e->id} | Room: {$e->room_id} | Date: {$e->date} | Name: {$e->event_name} | Status: {$e->status} | Time: {$e->start_time}-{$e->end_time}\n";
}
