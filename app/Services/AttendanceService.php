<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceService
{
    public function processAttendanceFile($file)
    {
        $data = $this->readFile($file);
        $groupedData = $this->groupByStudent($data);
        return $this->calculateResults($groupedData);
    }

    private function readFile($file)
    {
        $data = [];
        $handle = fopen($file, 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = [
                'student_name' => $row[0],
                'group_name' => $row[1],
                'subgroup' => $row[2] ?: 1, // Default to subgroup 1 if not specified
                'date' => Carbon::createFromFormat('d.m.Y', $row[3]),
                'time' => $row[4],
                'type' => $row[5],
                'number' => (int)$row[6],
                'subgroups' => (int)$row[7],
                'visit' => $row[8] === 'true',
                'has_credit' => $row[9] === 'true'
            ];
        }
        
        fclose($handle);
        return $data;
    }

    private function groupByStudent($data)
    {
        $grouped = [];
        foreach ($data as $record) {
            $key = $record['group_name'] . '_' . $record['student_name'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'group_name' => $record['group_name'],
                    'student_name' => $record['student_name'],
                    'subgroup' => $record['subgroup'],
                    'lessons' => [],
                    'has_credit' => $record['has_credit']
                ];
            }
            $grouped[$key]['lessons'][] = [
                'date' => $record['date']->format('d.m.Y'),
                'time' => $record['time'],
                'type' => $record['type'],
                'number' => $record['number'],
                'subgroups' => $record['subgroups'],
                'visit' => $record['visit']
            ];
        }
        return $grouped;
    }

    private function calculateResults($groupedData)
    {
        $results = [];
        foreach ($groupedData as $key => $studentData) {
            $groupName = $studentData['group_name'];
            if (!isset($results[$groupName])) {
                $results[$groupName] = [
                    'group_name' => $groupName,
                    'students' => [],
                    'result' => ['success' => 0, 'unsuccessfully' => 0]
                ];
            }

            $studentResult = $this->calculateStudentResult($studentData);
            $results[$groupName]['students'][] = $studentResult;
            
            if ($studentResult['result']) {
                $results[$groupName]['result']['success']++;
            } else {
                $results[$groupName]['result']['unsuccessfully']++;
            }
        }

        return array_values($results);
    }

    private function calculateStudentResult($studentData)
    {
        // If student already has credit, return positive result
        if ($studentData['has_credit']) {
            return [
                'name' => $studentData['student_name'],
                'subgroup' => $studentData['subgroup'],
                'lessons' => $studentData['lessons'],
                'visit_percent' => 100,
                'success_labs_percent' => 100,
                'success_labs' => count($studentData['lessons']),
                'result' => true
            ];
        }

        // Get all lessons for the student's group
        $allLessons = $studentData['lessons'];
        
        // Count total lessons and visited lessons
        $totalLessons = count($allLessons);
        $visitedLessons = count(array_filter($allLessons, function($lesson) {
            return $lesson['visit'];
        }));

        // Get lab lessons and count successful ones
        $labLessons = array_filter($allLessons, function($lesson) {
            return $lesson['type'] === 'lab';
        });

        $successfulLabs = count(array_filter($labLessons, function($lesson) {
            return $lesson['visit'];
        }));

        // Calculate percentages
        $visitPercent = $totalLessons > 0 ? ($visitedLessons / $totalLessons) * 100 : 0;
        $successLabsPercent = count($labLessons) > 0 ? ($successfulLabs / count($labLessons)) * 100 : 0;

        // Student gets credit if they have 80% attendance and completed required labs
        $result = $visitPercent >= 80 && $successLabsPercent >= 80;

        return [
            'name' => $studentData['student_name'],
            'subgroup' => $studentData['subgroup'],
            'lessons' => $studentData['lessons'],
            'visit_percent' => round($visitPercent, 2),
            'success_labs_percent' => round($successLabsPercent, 2),
            'success_labs' => $successfulLabs,
            'result' => $result
        ];
    }

    public function getAutomaticCreditStudents($results)
    {
        $automaticCreditStudents = [];
        foreach ($results as $group) {
            foreach ($group['students'] as $student) {
                if ($student['result']) {
                    $automaticCreditStudents[] = [
                        'name' => $student['name'],
                        'group' => $group['group_name'],
                        'visit_percent' => $student['visit_percent'],
                        'success_labs_percent' => $student['success_labs_percent']
                    ];
                }
            }
        }
        return $automaticCreditStudents;
    }
} 