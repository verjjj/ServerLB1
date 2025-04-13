<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use App\DTO\ChangeLogCollectionDTO;
use Illuminate\Http\Request;

class ChangeLogController extends Controller
{
    public function getHistory($id)
    {
        $logs = ChangeLog::where('entity_id', $id)->get();
        return response()->json(new ChangeLogCollectionDTO($logs));
    }

    public function rollback(Request $request, $id)
    {
        $log = ChangeLog::find($id);
        if (!$log) {
            return response()->json(['error' => 'Log not found'], 404);
        }

        // Логика отката изменений
        return response()->json(['message' => 'Rollback successful']);
    }
}
