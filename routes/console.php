<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('files:cleanup')->dailyAt('00:00');
