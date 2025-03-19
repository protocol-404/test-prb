<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendApplicationConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The application instance.
     *
     * @var \App\Models\Application
     */
    protected $application;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Application  $application
     * @return void
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = User::find($this->application->user_id);
        $jobOffer = $this->application->jobOffer;
        
        // In a real application, we would send an email here
        // Mail::to($user->email)->send(new \App\Mail\ApplicationConfirmation($user, $jobOffer));
        
        // For now, we'll just log the email
        \Log::info("Application confirmation email sent to {$user->email} for job offer: {$jobOffer->title}");
    }
}
