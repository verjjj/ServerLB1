<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:5120', // 5MB max
            'description' => 'nullable|string|max:255'
        ]);

        try {
            $file = $this->fileService->uploadUserPhoto(
                Auth::user(),
                $request->file('photo'),
                $request->input('description')
            );
    
            return response()->json([
                'message' => 'Photo uploaded successfully',
                'file' => $file
            ]);
        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'File upload failed', 'error' => $e->getMessage()], 400);
        }
    }

    public function deletePhoto()
    {
        $user = Auth::user();
        
        try {
            $this->fileService->deleteUserPhoto($user);
            return response()->json([
                'message' => 'Photo deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('File deletion failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'File deletion failed', 'error' => $e->getMessage()], 400);
        }
    }

    public function downloadPhoto(File $file)
    {
        if ($file->user && $file->user->id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        if (!Storage::disk('private')->exists($file->path)) {
             abort(404, 'File not found');
        }

        return response()->download(
            Storage::disk('private')->path($file->path),
            $file->original_name,
            ['Content-Type' => $file->mime_type]
        );
    }

    public function getAvatar(File $file)
    {
        if ($file->user && $file->user->id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        $avatarPath = $this->fileService->getAvatarPath($file);

        if (!Storage::disk('private')->exists($avatarPath)) {
             abort(404, 'Avatar not found');
        }

        return response()->file(
            Storage::disk('private')->path($avatarPath),
            ['Content-Type' => $file->mime_type]
        );
    }

    public function downloadPhotosArchive()
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        try {
            $zipPath = $this->fileService->createPhotosArchive();
            return response()->download($zipPath)->deleteFileAfterSend();
        } catch (\Exception $e) {
            Log::error('Archive creation failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Archive creation failed', 'error' => $e->getMessage()], 400);
        }
    }
} 