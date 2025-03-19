<?php

namespace App\Exports;

use App\Models\Application;
use App\Models\JobOffer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApplicationsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $recruiterId;
    
    /**
     * Create a new export instance.
     *
     * @param  int  $recruiterId
     * @return void
     */
    public function __construct($recruiterId)
    {
        $this->recruiterId = $recruiterId;
    }
    
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $jobOfferIds = JobOffer::where('recruiter_id', $this->recruiterId)->pluck('id');
        
        return Application::whereIn('job_offer_id', $jobOfferIds)
            ->with(['user', 'jobOffer'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Candidate Name',
            'Email',
            'Phone',
            'Job Title',
            'Status',
            'Application Date',
        ];
    }
    
    /**
     * @param  mixed  $application
     * @return array
     */
    public function map($application): array
    {
        return [
            $application->user->name,
            $application->user->email,
            $application->user->phone_number ?? 'N/A',
            $application->jobOffer->title,
            $application->status,
            $application->created_at->format('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $sheet
     * @return void
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
