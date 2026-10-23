<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Course;
use App\Services\TelegramChannelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Publishes a course announcement to the Telegram channel outside the admin
 * request, so a slow or failing Telegram API never blocks the panel.
 *
 * The broadcast is deliberately not retried: a retry would publish the same
 * announcement twice in the channel. A failed attempt is logged and the admin
 * can dispatch it again from the campaign action.
 */
class BroadcastCourseToTelegram implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public int $courseId) {}

    public function handle(TelegramChannelService $service): void
    {
        $course = Course::query()->with(['groups'])->find($this->courseId);

        if ($course === null) {
            return;
        }

        $result = $service->broadcastCourse($course);

        if ($result['success'] === false) {
            Log::warning('Telegram broadcast job finished without publishing', [
                'course_id' => $course->id,
                'message' => $result['message'],
            ]);
        }
    }
}
