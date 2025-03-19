<?php

namespace App\Console\Commands;

use App\Jobs\GenerateWeeklyApplicationReport;
use App\Models\User;
use Illuminate\Console\Command;

class SendWeeklyReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:weekly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send weekly application reports to recruiters';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $recruiters = User::where('role', 'recruiter')->orWhere('role', 'admin')->get();
        
        $this->info('Found ' . $recruiters->count() . ' recruiters');
        
        foreach ($recruiters as $recruiter) {
            $this->info('Dispatching report generation job for recruiter: ' . $recruiter->email);
            GenerateWeeklyApplicationReport::dispatch($recruiter->id);
        }
        
        $this->info('Weekly report jobs dispatched for ' . $recruiters->count() . ' recruiters');
        
        return Command::SUCCESS;
    }
}
