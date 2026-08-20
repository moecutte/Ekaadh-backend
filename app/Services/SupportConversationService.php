<?php

namespace App\Services;

use App\Models\SupportConversation;
use App\Models\SupportFaq;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupportConversationService
{
    public function __construct(private PushNotificationService $push) {}

    public function ensureGuestToken(Request $request): string
    {
        $token = (string) $request->session()->get('support_guest_token', '');
        if ($token === '') {
            $token = (string) Str::uuid();
            $request->session()->put('support_guest_token', $token);
        }

        return $token;
    }

    public function openForWeb(?User $user, Request $request, string $channel = 'web'): SupportConversation
    {
        if ($user) {
            return $this->findOrCreateOpen($user->id, null, $channel, $user->name, $this->contactForUser($user));
        }

        $guestToken = $this->ensureGuestToken($request);

        return $this->findOrCreateOpen(null, $guestToken, $channel, null, null);
    }

    public function openForMobile(?User $user, ?string $guestToken, string $channel = 'mobile'): SupportConversation
    {
        if ($user) {
            return $this->findOrCreateOpen($user->id, null, $channel, $user->name, $this->contactForUser($user));
        }

        $guestToken = trim((string) $guestToken);
        if ($guestToken === '') {
            throw ValidationException::withMessages([
                'guest_token' => ['A guest token is required when not signed in.'],
            ]);
        }

        return $this->findOrCreateOpen(null, $guestToken, $channel, null, null);
    }

    public function authorizeWeb(SupportConversation $conversation, ?User $user, Request $request): void
    {
        if ($user && $conversation->user_id === $user->id) {
            return;
        }

        if (! $user && $conversation->guest_token && $conversation->guest_token === $request->session()->get('support_guest_token')) {
            return;
        }

        abort(403);
    }

    public function authorizeMobile(SupportConversation $conversation, ?User $user, ?string $guestToken): void
    {
        if ($user && $conversation->user_id === $user->id) {
            return;
        }

        if (! $user && $conversation->guest_token && $conversation->guest_token === trim((string) $guestToken)) {
            return;
        }

        abort(403);
    }

    public function addCustomerMessage(
        SupportConversation $conversation,
        string $body,
        ?User $user = null,
        ?SupportFaq $faq = null,
    ): SupportMessage {
        if ($faq) {
            return $this->addMessage($conversation, SupportMessage::SENDER_SYSTEM, $faq->answer, null, $faq);
        }

        $message = $this->addMessage($conversation, SupportMessage::SENDER_CUSTOMER, $body, $user);
        $who = $user?->name ?: ($conversation->customer_name ?: 'A customer');
        $preview = mb_strlen($body) > 120 ? mb_substr($body, 0, 117).'…' : $body;
        app(PanelNotifier::class)->toAdmins(
            'New support message',
            "{$who}: {$preview}",
            'support_message',
            route('admin.support.conversations.show', $conversation),
            ['conversation_id' => (string) $conversation->id],
        );

        return $message;
    }

    public function addAdminMessage(SupportConversation $conversation, User $admin, string $body): SupportMessage
    {
        $message = $this->addMessage($conversation, SupportMessage::SENDER_ADMIN, $body, $admin);

        if ($conversation->user_id) {
            $customer = User::query()->find($conversation->user_id);
            if ($customer) {
                $preview = mb_strlen($body) > 120 ? mb_substr($body, 0, 117).'…' : $body;
                $this->push->sendToUser(
                    $customer,
                    'Support replied',
                    $preview,
                    PushNotificationService::TYPE_SUPPORT_REPLY,
                    [
                        'conversation_id' => (string) $conversation->id,
                        'message_id' => (string) $message->id,
                    ],
                    true,
                );
            }
        }

        return $message;
    }

    private function findOrCreateOpen(
        ?int $userId,
        ?string $guestToken,
        string $channel,
        ?string $customerName,
        ?string $customerContact,
    ): SupportConversation {
        $query = SupportConversation::query()->open();

        if ($userId) {
            $existing = (clone $query)->where('user_id', $userId)->first();
        } else {
            $existing = (clone $query)->where('guest_token', $guestToken)->first();
        }

        if ($existing) {
            return $existing;
        }

        return SupportConversation::query()->create([
            'user_id' => $userId,
            'guest_token' => $userId ? null : $guestToken,
            'channel' => $channel,
            'status' => SupportConversation::STATUS_OPEN,
            'customer_name' => $customerName,
            'customer_contact' => $customerContact,
        ]);
    }

    private function addMessage(
        SupportConversation $conversation,
        string $senderType,
        string $body,
        ?User $sender = null,
        ?SupportFaq $faq = null,
    ): SupportMessage {
        $message = $conversation->messages()->create([
            'sender_type' => $senderType,
            'sender_user_id' => $sender?->id,
            'support_faq_id' => $faq?->id,
            'body' => trim($body),
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        return $message;
    }

    private function contactForUser(User $user): ?string
    {
        $email = (string) $user->email;
        if ($email !== '' && ! str_ends_with(strtolower($email), '@ekaadh.local')) {
            return $email;
        }

        return $user->phone;
    }
}
