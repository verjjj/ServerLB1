<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    private FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:5120' // 5MB max
        ]);

        try {
            $file = $this->fileService->uploadUserPhoto(Auth::user(), $request->file('photo'));
            return response()->json(['message' => 'Photo uploaded successfully', 'file' => $file]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function deletePhoto()
    {
        try {
            $this->fileService->deleteUserPhoto(Auth::user());
            return response()->json(['message' => 'Photo deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function downloadPhoto(File $file)
    {
        if ($file->user && $file->user->id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $content = $this->fileService->downloadOriginalPhoto($file);
            return response($content)
                ->header('Content-Type', 'image/' . $file->format)
                ->header('Content-Disposition', 'attachment; filename="' . $file->name . '"');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function downloadAvatar(File $file)
    {
        if ($file->user && $file->user->id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $avatarPath = $this->fileService->getAvatarPath($file);
            $content = Storage::disk('private')->get($avatarPath);
            return response($content)
                ->header('Content-Type', 'image/' . $file->format)
                ->header('Content-Disposition', 'inline; filename="avatar_' . $file->name . '"');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function downloadPhotosArchive()
    {
        if (!Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $zipPath = $this->fileService->createPhotosArchive();
            return response()->download($zipPath)->deleteFileAfterSend();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
} 