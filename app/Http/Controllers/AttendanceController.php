<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function processAttendance(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $results = $this->attendanceService->processAttendanceFile($file->getPathname());
        $automaticCreditStudents = $this->attendanceService->getAutomaticCreditStudents($results);

        return response()->json([
            'groups' => $results,
            'automatic_credit_students' => $automaticCreditStudents
        ]);
    }
} 