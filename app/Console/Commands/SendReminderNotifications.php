<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use App\Models\User;
use App\Models\DeviceToken;
use App\Models\OutNotification;
use App\Services\FCMService;
use Carbon\Carbon;

class SendReminderNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for active reminders that match current time and day';

    protected $fcmService;

    public function __construct(FCMService $fcmService)
    {
        parent::__construct();
        $this->fcmService = $fcmService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting reminder notifications job...');
        
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $currentDay = strtolower($now->format('l')); // monday, tuesday, etc.
        
        $this->info("Current time: {$currentTime}, Current day: {$currentDay}");

        // Find active reminders that match current time and day
        $reminders = Reminder::active()
            ->whereTime('time', '=', $currentTime)
            ->where(function ($query) use ($currentDay) {
                $query->whereJsonContains('days', $currentDay)
                      ->orWhereNull('days')
                      ->orWhere('days', []);

            })
            ->with(['user', 'element'])
            ->get();

        $this->info("Found {$reminders->count()} matching reminders");

        $sentCount = 0;

        foreach ($reminders as $reminder) {
            try {
                $user = $reminder->user;
                
                if (!$user) {
                    $this->warn("Reminder {$reminder->id}: User not found");
                    continue;
                }

                // Get user's device tokens
                $deviceTokens = DeviceToken::where('user_id', $user->id)->get();
                
                if ($deviceTokens->isEmpty()) {
                    $this->warn("Reminder {$reminder->id}: No device tokens found for user {$user->id}");
                    continue;
                }

                // Prepare notification content
                $title = $this->getNotificationTitle($reminder);
                $body = $this->getNotificationBody($reminder);
                $image = $this->getNotificationImage($reminder);

                // Create OutNotification record
                $outNotification = OutNotification::create([
                    'type' => 'reminder',
                    'title' => $title,
                    'description' => $body,
                    'image' => $image,
                    'user_ids' => [$user->id],
                    'guest_ids' => [],
                    'action_url' => $this->getActionUrl($reminder),
                    'seen' => false,
                ]);

                // Send FCM notification to all user's devices
                foreach ($deviceTokens as $deviceToken) {
                    $this->fcmService->sendNotification(
                        $deviceToken->token,
                        $title,
                        $body,
                        $image,
                        'reminder',
                        $outNotification->id
                    );
                }

                $sentCount++;
                $this->info("Sent reminder notification to user {$user->id} ({$user->name})");

            } catch (\Exception $e) {
                $this->error("Error sending reminder {$reminder->id}: " . $e->getMessage());
            }
        }

        $this->info("Reminder notifications job completed. Sent {$sentCount} notifications.");
        
        return Command::SUCCESS;
    }

    /**
     * Get notification title based on reminder type
     */
    private function getNotificationTitle($reminder)
    {
        $elementType = class_basename($reminder->element_type);
        
        switch ($elementType) {
            case 'Remedy':
                return 'Remedy Reminder';
            case 'Course':
                return 'Course Reminder';
            case 'Lesson':
                return 'Lesson Reminder';
            default:
                return 'Reminder';
        }
    }

    /**
     * Get notification body based on reminder and element
     */
    private function getNotificationBody($reminder)
    {
        $element = $reminder->element;
        $elementType = class_basename($reminder->element_type);
        
        if (!$element) {
            return "It's time for your scheduled reminder!";
        }

        switch ($elementType) {
            case 'Remedy':
                return "Don't forget to take your remedy: " . $element->name;
            case 'Course':
                return "Time to continue your course: " . $element->title;
            case 'Lesson':
                return "Time for your lesson: " . $element->title;
            default:
                $name = $element->name ?? $element->title ?? 'your scheduled item';
                return "Reminder: " . $name;
        }
    }

    /**
     * Get notification image based on element
     */
    private function getNotificationImage($reminder)
    {
        $element = $reminder->element;
        
        if ($element && isset($element->image)) {
            return $element->image;
        }
        
        // Default reminder icon/image
        return null;
    }

    /**
     * Get action URL for the notification
     */
    private function getActionUrl($reminder)
    {
        $elementType = class_basename($reminder->element_type);
        
        switch ($elementType) {
            case 'Remedy':
                return "/remedies/{$reminder->element_id}";
            case 'Course':
                return "/courses/{$reminder->element_id}";
            case 'Lesson':
                return "/lessons/{$reminder->element_id}";
            default:
                return null;
        }
    }
}
