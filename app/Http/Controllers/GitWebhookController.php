<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
class GitWebhookController extends Controller
{
    public function handle(Request $request)
    {
        if ($this->isUpdateInProgress()) {
            return response()->json(['message' => 'Update is already in progress. Please try again later.'], 423);
        }
        $this->lockUpdate();

        try {
            $secretKey = $request->input('secret_key');
            
            // Validate secret key length and format
            if (strlen($secretKey) !== 36) {
                return response()->json(['message' => 'Invalid secret key format'], 403);
            }
            
            if ($secretKey !== config('app.git_webhook_secret')) {
                return response()->json(['message' => 'Invalid secret key'], 403);
            }
            $ip = $request->ip();
            Log::info("Git hook triggered by IP: $ip");
            $this->switchToMainBranch();
            $this->discardChanges();
            $this->pullLatestChanges();
            return response()->json(['message' => 'Project updated successfully'], 200);
        } catch (\Exception $e) {
            Log::error("Error during git update: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update project'], 500);
        } finally {
            $this->unlockUpdate();
        }
    }
    private function isUpdateInProgress()
    {
        return file_exists(storage_path('app/update.lock'));
    }
    private function lockUpdate()
    {
        file_put_contents(storage_path('app/update.lock'), time());
    }
    private function unlockUpdate()
    {
        if (file_exists(storage_path('app/update.lock'))) {
            unlink(storage_path('app/update.lock'));
        }
    }
    private function switchToMainBranch()
    {
        exec('git checkout main 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            throw new \Exception("Failed to switch to main branch: " . implode("\n", $output));
        }
        Log::info("Switched to main branch");
    }
    private function discardChanges()
    {
        exec('git reset --hard HEAD 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            throw new \Exception("Failed to discard changes: " . implode("\n", $output));
        }
        Log::info("Discarded all changes");
    }
    private function pullLatestChanges()
    {
        exec('git pull origin main 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            throw new \Exception("Failed to pull latest changes: " . implode("\n", $output));
        }
        Log::info("Pulled latest changes from repository");
    }
}
