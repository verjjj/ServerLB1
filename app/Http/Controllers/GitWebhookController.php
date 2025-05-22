<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitWebhookController extends Controller
{
    private $lockFile = 'update.lock';

    public function handle(Request $request)
    {
        if (file_exists($this->getLockPath())) {
            return response()->json([
                'message' => 'Update is already in progress.'
            ], 423);
        }

        file_put_contents($this->getLockPath(), date('Y-m-d H:i:s'));

        try {
            if ($request->input('secret_key') !== config('app.git_webhook_secret')) {
                return response()->json([
                    'message' => 'Invalid secret key.'
                ], 403);
            }

            Log::info('Started update with IP: ' . $request->ip());

//            $this->runCommand('git reset --hard');
//            $this->runCommand('git checkout main');
//            $this->runCommand('git pull origin main');

            return response()->json([
                'message' => 'Project updated successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error during git update: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update project'
            ], 500);

        } finally {
            if (file_exists($this->getLockPath())) {
                unlink($this->getLockPath());
            }
        }
    }

    private function runCommand($command)
    {
        exec($command . ' 2>&1', $output, $code);

        if ($code !== 0) {
            throw new \Exception(implode("\n", $output));
        }

        Log::info('Выполнено: ' . $command);
    }

    private function getLockPath()
    {
        return storage_path('app/' . $this->lockFile);
    }
}
