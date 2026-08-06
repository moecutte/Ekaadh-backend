<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use App\Services\PushNotificationService;
use App\Support\Phone;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendEventRemindersCommand extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send push reminders for events starting in about 24 hours';

    public function handle(PushNotificationService $push): int
    {
        $windowStart = now()->addHours(23);
        $windowEnd = now()->addHours(25);

        $tickets = Ticket::query()
            ->with(['event', 'orderItem.order', 'invitation'])
            ->where('status', 'valid')
            ->whereNull('reminder_24h_sent_at')
            ->whereHas('event', fn ($q) => $q->where('status', 'published'))
            ->get()
            ->filter(function (Ticket $ticket) use ($windowStart, $windowEnd) {
                $event = $ticket->event;
                if (! $event?->event_date) {
                    return false;
                }

                $time = $event->event_time ?: '18:00:00';
                if (strlen($time) === 5) {
                    $time .= ':00';
                }

                $starts = Carbon::parse($event->event_date->format('Y-m-d').' '.$time);

                return $starts->between($windowStart, $windowEnd);
            });

        $sentUsers = [];
        $count = 0;

        foreach ($tickets as $ticket) {
            $userIds = $this->recipientUserIds($ticket);
            $title = $ticket->event?->title ?? 'your event';

            foreach ($userIds as $userId) {
                $key = $userId.'|'.$ticket->event_id;
                if (isset($sentUsers[$key])) {
                    continue;
                }
                $sentUsers[$key] = true;

                $user = User::query()->find($userId);
                if (! $user) {
                    continue;
                }

                $push->sendToUser(
                    $user,
                    'Event tomorrow',
                    "{$title} starts in about 24 hours. Open Ekaadh for your tickets.",
                    PushNotificationService::TYPE_EVENT_REMINDER,
                    [
                        'event_id' => (string) $ticket->event_id,
                        'ticket_code' => (string) $ticket->ticket_code,
                    ],
                );
                $count++;
            }

            $ticket->update(['reminder_24h_sent_at' => now()]);
        }

        $this->info("Sent {$count} event reminder push(es) for {$tickets->count()} ticket(s).");

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function recipientUserIds(Ticket $ticket): array
    {
        $ids = [];

        $orderUserId = $ticket->orderItem?->order?->user_id;
        if ($orderUserId) {
            $ids[] = (int) $orderUserId;
        }

        $phone = $ticket->invitation?->guest_phone
            ?: $ticket->orderItem?->order?->buyer_phone;

        if ($phone) {
            $matched = User::query()
                ->whereIn('phone', Phone::variants($phone))
                ->pluck('id')
                ->all();
            $ids = array_merge($ids, $matched);
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }
}
