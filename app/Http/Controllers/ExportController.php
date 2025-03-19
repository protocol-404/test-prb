<?php

namespace App\Http\Controllers;

use App\Exports\ApplicationsExport;
use App\Models\Application;
use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /**
     * Create a new ExportController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.verify');
    }

    /**
     * Export applications as Excel file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel()
    {
        $user = Auth::user();
        
        // Check if user is a recruiter
        if (!$user->isRecruiter()) {
            return response()->json(['message' => 'Unauthorized. Only recruiters can export applications.'], 403);
        }
        
        return Excel::download(new ApplicationsExport($user->id), 'applications.xlsx');
    }

    /**
     * Export applications as CSV file.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportCsv()
    {
        $user = Auth::user();
        
        // Check if user is a recruiter
        if (!$user->isRecruiter()) {
            return response()->json(['message' => 'Unauthorized. Only recruiters can export applications.'], 403);
        }
        
        return Excel::download(new ApplicationsExport($user->id), 'applications.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * Generate and download the latest weekly report.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function downloadLatestWeeklyReport()
    {
        $user = Auth::user();
        
        // Check if user is a recruiter
        if (!$user->isRecruiter()) {
            return response()->json(['message' => 'Unauthorized. Only recruiters can download reports.'], 403);
        }
        
        // Find the latest report for this recruiter
        $pattern = 'reports/weekly_report_*_recruiter_' . $user->id . '.csv';
        $files = Storage::disk('local')->files('reports');
        
        $matchingFiles = [];
        foreach ($files as $file) {
            if (fnmatch($pattern, $file)) {
                $matchingFiles[] = $file;
            }
        }
        
        // Sort by creation date (newest first)
        usort($matchingFiles, function($a, $b) use ($files) {
            return Storage::disk('local')->lastModified($b) - Storage::disk('local')->lastModified($a);
        });
        
        if (empty($matchingFiles)) {
            return response()->json(['message' => 'No weekly reports found'], 404);
        }
        
        $latestReport = $matchingFiles[0];
        $filename = basename($latestReport);
        
        return Storage::disk('local')->download($latestReport, $filename);
    }
}
