<?php

use Illuminate\Support\Facades\Schedule;

// Крон на сервере: * * * * * cd /var/www/caffe_app && php artisan schedule:run
Schedule::command('checklists:notify-overdue')->everyFiveMinutes()->withoutOverlapping();
