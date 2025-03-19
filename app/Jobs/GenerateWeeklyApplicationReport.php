<?php

namespace App\Jobs;

use App\Models\JobOffer;
use App\Models\Application;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GenerateWeeklyApplicationReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The recruiter user ID.
     *
     * @var int
     */
    protected $recruiterId;

    /**
     * Create a new job instance.
     *
     * @param  int  $recruiterId
     * @return void
     */
    public function __construct($recruiterId)
    {
        $this->recruiterId = $recruiterId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $recruiter = User::find($this->recruiterId);
        
        // Only process for recruiters
        if (!$recruiter || !$recruiter->isRecruiter()) {
            return;
        }
        
        // Get all job offers by this recruiter
        $jobOfferIds = JobOffer::where('recruiter_id', $this->recruiterId)->pluck('id');
        
        // Get applications from the past week
        $applications = Application::whereIn('job_offer_id', $jobOfferIds)
            ->whereBetween('created_at', [now()->subWeek(), now()])
            ->with(['user', 'jobOffer'])
            ->get();
            
        // Generate CSV
        $csv = $this->generateCsv($applications);
        $filename = 'weekly_report_' . now()->format('Y-m-d') . '_recruiter_' . $this->recruiterId . '.csv';
        
        // Store CSV
        Storage::disk('local')->put('reports/' . $filename, $csv);
        
        // In a real application, we would send an email with the report
        // Mail::to($recruiter->email)->send(new \App\Mail\WeeklyApplicationReport($recruiter, $filename, $applications->count()));
        
        // For now, we'll just log the email
        \Log::info("Weekly application report generated for recruiter {$recruiter->email} with {$applications->count()} applications");
    }
    
    /**
     * Generate CSV content from applications.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $applications
     * @return string
     */
    private function generateCsv($applications)
    {
        $csv = "Candidate Name,Email,Job Title,Status,Application Date\n";
        
        foreach ($applications as $application) {
            $csv .= '"' . $application->user->name . '",';
            $csv .= '"' . $application->user->email . '",';
            $csv .= '"' . $application->jobOffer->title . '",';
            $csv .= '"' . $application->status . '",';
            $csv .= '"' . $application->created_at->format('Y-m-d H:i:s') . '"';
            $csv .= "\n";
        }
        
        return $csv;
    }
}
