<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogRequest;
use Illuminate\Http\Request;
use App\DTO\LogRequestDto;
use App\DTO\LogRequestCollectionDto;

class LogRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LogRequest::query();

        if ($filters = $request->input('filter')) {
            foreach ($filters as $filter) {
                if (!isset($filter['key'], $filter['value'])) continue;
                switch ($filter['key']) {
                    case 'user_id':
                    case 'response_status':
                        $query->where($filter['key'], $filter['value']);
                        break;
                    case 'ip_address':
                    case 'user_agent':
                    case 'controller_path':
                        $query->where($filter['key'], 'like', '%' . $filter['value'] . '%');
                        break;
                }
            }
        }

        if ($sorts = $request->input('sortBy')) {
            foreach ($sorts as $sort) {
                if (!isset($sort['key'], $sort['order'])) continue;
                $query->orderBy($sort['key'], $sort['order'] === 'desc' ? 'desc' : 'asc');
            }
        } else {
            $query->orderByDesc('called_at');
        }

        $perPage = (int)($request->input('count', 10));
        $page = (int)($request->input('page', 1));
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $log) {
            $items[] = [
                'id' => $log->id,
                'full_url' => $log->full_url,
                'controller_path' => $log->controller_path,
                'controller_method' => $log->controller_method,
                'response_status' => $log->response_status,
                'called_at' => $log->called_at,
            ];
        }

        return response()->json([
            'data' => $items,
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
        ]);
    }

    public function show($id)
    {
        $log = LogRequest::findOrFail($id);

        return response()->json([
            'id' => $log->id,
            'full_url' => $log->full_url,
            'http_method' => $log->http_method,
            'controller_path' => $log->controller_path,
            'controller_method' => $log->controller_method,
            'request_body' => $log->request_body,
            'request_headers' => $log->request_headers,
            'user_id' => $log->user_id,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'response_status' => $log->response_status,
            'response_body' => $log->response_body,
            'response_headers' => $log->response_headers,
            'called_at' => $log->called_at,
            'created_at' => $log->created_at,
            'updated_at' => $log->updated_at,
        ]);
    }
}
