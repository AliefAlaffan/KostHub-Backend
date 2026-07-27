<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\GenerateMonthlyInvoices;
use App\Jobs\MarkContractsEndingSoon;
use App\Jobs\MarkOverdueInvoicesAndApplyFines;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new GenerateMonthlyInvoices)->monthlyOn(1, '01:00');
Schedule::job(new MarkOverdueInvoicesAndApplyFines)->dailyAt('00:30');
Schedule::job(new MarkContractsEndingSoon)->dailyAt('01:30');
