<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestDispatch extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:dispatch';

    /**
     * The console command description.
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \App\Events\JobRequestCreated::dispatch('TEST MESSAGE', 1);
        $this->info("Event dispatched!");
    }
}
