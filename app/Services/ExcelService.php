<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExcelService
{
    private const IMPORT_MODES = [
        'append' => 'append',
        'overwrite' => 'overwrite',
    ];

    private const ERROR_HANDLING_MODES = [
        'continue' => 'continue',
        'abort' => 'abort',
        'collect' => 'collect',
    ];

    public function export(Collection $data, array $columns, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $column = 1;
        foreach ($columns as $header) {
            $sheet->setCellValue($sheet->getCell([$column, 1])->getCoordinate(), $header);
            $column++;
        }

        // Set data
        $row = 2;
        foreach ($data as $item) {
            $column = 1;
            foreach ($columns as $key => $header) {
                $sheet->setCellValue($sheet->getCell([$column, $row])->getCoordinate(), $item->$key);
                $column++;
            }
            $row++;
        }

        // Save file
        $writer = new Xlsx($spreadsheet);
        $path = storage_path('app/public/exports/' . $filename);
        $writer->save($path);

        return $path;
    }

    public function import(
        string $filePath,
        string $modelClass,
        array $columns,
        string $importMode = 'append',
        string $errorHandlingMode = 'continue'
    ): array {
        if (!in_array($importMode, self::IMPORT_MODES)) {
            throw new \InvalidArgumentException('Invalid import mode');
        }

        if (!in_array($errorHandlingMode, self::ERROR_HANDLING_MODES)) {
            throw new \InvalidArgumentException('Invalid error handling mode');
        }

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Remove header row
        array_shift($rows);

        $results = [
            'success' => [],
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and array is 0-based
                $data = array_combine($columns, $row);

                try {
                    if ($importMode === self::IMPORT_MODES['append']) {
                        $model = new $modelClass();
                        foreach ($data as $key => $value) {
                            $model->$key = $value;
                        }
                        $model->save();
                        $results['success'][] = [
                            'row' => $rowNumber,
                            'id' => $model->id,
                            'message' => "Record #{$rowNumber} successfully added with ID #{$model->id}"
                        ];
                    } else {
                        // Overwrite mode
                        $model = $modelClass::where('id', $data['id'])->first();
                        if ($model) {
                            foreach ($data as $key => $value) {
                                $model->$key = $value;
                            }
                            $model->save();
                            $results['success'][] = [
                                'row' => $rowNumber,
                                'id' => $model->id,
                                'message' => "Record #{$rowNumber} successfully updated record with ID #{$model->id}"
                            ];
                        } else {
                            $model = new $modelClass();
                            foreach ($data as $key => $value) {
                                $model->$key = $value;
                            }
                            $model->save();
                            $results['success'][] = [
                                'row' => $rowNumber,
                                'id' => $model->id,
                                'message' => "Record #{$rowNumber} successfully added with ID #{$model->id}"
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    $errorMessage = "Record #{$rowNumber} failed to add/update. Unknown error.";
                    
                    // Check for duplicate entry
                    if (str_contains($e->getMessage(), 'Duplicate entry')) {
                        $errorMessage = "Record #{$rowNumber} contains duplicate data";
                    }

                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'message' => $errorMessage
                    ];

                    if ($errorHandlingMode === self::ERROR_HANDLING_MODES['abort']) {
                        throw $e;
                    }
                }

                if ($errorHandlingMode === self::ERROR_HANDLING_MODES['collect'] && !empty($results['errors'])) {
                    DB::rollBack();
                    return $results;
                }
            }

            if ($errorHandlingMode === self::ERROR_HANDLING_MODES['collect'] && !empty($results['errors'])) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }
} 