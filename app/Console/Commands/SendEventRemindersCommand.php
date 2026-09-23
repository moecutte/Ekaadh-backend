<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use App\Services\PushNotificationService;
use App\Services\TicketDeliveryService;
use App\Support\Phone;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendEventRemindersCommand extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send 24h push and 2h SMS/push reminders for upcoming events';

    public function handle(PushNotificationService $push, TicketDeliveryService $delivery): int
    {
        $push24 = $this->send24hPushReminders($push);
        $sms2h = $this->send2hReminders($push, $delivery);

        $this->info("24h push: {$push24['pushes']} for {$push24['tickets']} ticket(s). "
            ."2h SMS: {$sms2h['sms']} / push: {$sms2h['pushes']} for {$sms2h['tickets']} ticket(s).");

        return self::SUCCESS;
    }

    /**
     * @return array{pushes: int, tickets: int}
     */
    private function send24hPushReminders(PushNotificationService $push): array
    {
        $tickets = $this->ticketsInWindow(
            now()->addHours(23),
            now()->addHours(25),
            'reminder_24h_sent_at',
        );

        $sentUsers = [];
        $count = 0;

        foreach ($tickets as $ticket) {
            $title = $ticket->event?->title ?? 'munaasabadda';

            foreach ($this->recipientUserIds($ticket) as $userId) {
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
                    'Xusuusin — berri',
                    "{$title} waxay bilaabanaysaa qiyaastii 24 saacadood. Fur Ekaadh si aad u aragto tigidhadaada.",
                    PushNotificationService::TYPE_EVENT_REMINDER,
                    [
                        'event_id' => (string) $ticket->event_id,
                        'ticket_code' => (string) $ticket->ticket_code,
                    ],
                    true,
                );
                $count++;
            }

            $ticket->update(['reminder_24h_sent_at' => now()]);
        }

        return ['pushes' => $count, 'tickets' => $tickets->count()];
    }

    /**
     * @return array{sms: int, pushes: int, tickets: int}
     */
    private function send2hReminders(PushNotificationService $push, TicketDeliveryService $delivery): array
    {
        $tickets = $this->ticketsInWindow(
            now()->addMinutes(90),
            now()->addMinutes(150),
            'reminder_2h_sent_at',
        );

        $sentPhones = [];
        $sentUsers = [];
        $smsCount = 0;
        $pushCount = 0;

        foreach ($tickets as $ticket) {
            $title = $ticket->event?->title ?? 'munaasabadda';
            $phone = $ticket->invitation?->guest_phone
                ?: $ticket->orderItem?->order?->buyer_phone;
            $phoneKey = $phone ? implode('|', Phone::variants($phone)) : '';

            if ($phoneKey !== '' && ! isset($sentPhones[$phoneKey])) {
                $sentPhones[$phoneKey] = true;
                $status = $delivery->sendEventReminderSms($ticket);
                if ($status === 'sent') {
                    $smsCount++;
                }
            }

            $userIds = $this->recipientUserIds($ticket);
            $pushed = false;
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
                    'Xusuusin — 2 saacadood',
                    "{$title} waxay bilaabanaysaa 2 saacadood gudahood. Fur Ekaadh si aad u aragto tigidhadaada.",
                    PushNotificationService::TYPE_EVENT_REMINDER,
                    [
                        'event_id' => (string) $ticket->event_id,
                        'ticket_code' => (string) $ticket->ticket_code,
                    ],
                    true,
                );
                $pushCount++;
                $pushed = true;
            }

            // Invitees / guests with no account still get a phone push once per event.
            if (! $pushed && $phone) {
                $phonePushKey = 'phone:'.$phoneKey.'|'.$ticket->event_id;
                if (! isset($sentUsers[$phonePushKey])) {
                    $sentUsers[$phonePushKey] = true;
                    $push->sendToPhone(
                        $phone,
                        'Xusuusin — 2 saacadood',
                        "{$title} waxay bilaabanaysaa 2 saacadood gudahood. Fur Ekaadh si aad u aragto tigidhadaada.",
                        PushNotificationService::TYPE_EVENT_REMINDER,
                        [
                            'event_id' => (string) $ticket->event_id,
                            'ticket_code' => (string) $ticket->ticket_code,
                        ],
                        true,
                    );
                    $pushCount++;
                }
            }

            $ticket->update(['reminder_2h_sent_at' => now()]);
        }

        return ['sms' => $smsCount, 'pushes' => $pushCount, 'tickets' => $tickets->count()];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Ticket>
     */
    private function ticketsInWindow(Carbon $windowStart, Carbon $windowEnd, string $sentColumn)
    {
        return Ticket::query()
            ->with(['event', 'orderItem.order', 'invitation'])
            ->where('status', 'valid')
            ->whereNull($sentColumn)
            ->whereHas('event', fn ($q) => $q->where('status', 'published'))
            ->get()
            ->filter(function (Ticket $ticket) use ($windowStart, $windowEnd) {
                $starts = $this->eventStartsAt($ticket);
                if (! $starts) {
                    return false;
                }

                return $starts->between($windowStart, $windowEnd);
            });
    }

    private function eventStartsAt(Ticket $ticket): ?Carbon
    {
        $event = $ticket->event;
        if (! $event?->event_date) {
            return null;
        }

        $time = $event->event_time ?: '18:00:00';
        if (strlen((string) $time) === 5) {
            $time .= ':00';
        }

        return Carbon::parse($event->event_date->format('Y-m-d').' '.$time);
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
