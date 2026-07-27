<?php

namespace App\Jobs;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkContractsEndingSoon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Contract::where('status', 'active')
            ->whereDate('end_date', '<=', now()->addDays(30)->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->update(['status' => 'ending_soon']);
    }
}