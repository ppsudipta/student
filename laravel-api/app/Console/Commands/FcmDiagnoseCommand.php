<?php

namespace App\Console\Commands;

use App\Models\StudentDeviceToken;
use App\Services\FcmService;
use Illuminate\Console\Command;

class FcmDiagnoseCommand extends Command
{
    protected $signature = 'fcm:diagnose {--student= : Optional student id to send a test push}';

    protected $description = 'Check FCM configuration and device tokens';

    public function handle(FcmService $fcm): int
    {
        $path = (string) config('services.fcm.credentials');
        $this->info('credentials path: '.$path);
        $this->info('credentials exists: '.(is_file($path) ? 'yes' : 'no'));
        $this->info('fcm configured: '.($fcm->isConfigured() ? 'yes' : 'no'));
        $this->info('push secret set: '.(config('services.fcm.push_secret') ? 'yes' : 'no'));

        $total = StudentDeviceToken::query()->count();
        $this->info('device tokens: '.$total);

        if ($total > 0) {
            $rows = StudentDeviceToken::query()
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'student_id', 'platform', 'updated_at']);
            foreach ($rows as $row) {
                $this->line("  #{$row->id} student={$row->student_id} platform={$row->platform} updated={$row->updated_at}");
            }
        } else {
            $this->warn('No device tokens yet. Open the Android app, allow notifications, and log in again.');
        }

        $studentId = $this->option('student');
        if ($studentId) {
            $result = $fcm->sendToStudents(
                [(int) $studentId],
                'Test notice',
                'FCM diagnose test push',
                ['type' => 'notice', 'screen' => 'notices']
            );
            $this->info('test send result: '.json_encode($result));
        }

        return self::SUCCESS;
    }
}
