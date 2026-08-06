<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportFaq;
use App\Models\User;
use App\Services\SupportConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class SupportController extends Controller
{
    public function __construct(private SupportConversationService $support) {}

    public function faqs(Request $request): JsonResponse
    {
        $locale = $this->locale($request);

        $faqs = SupportFaq::query()
            ->active()
            ->forLocale($locale)
            ->ordered()
            ->get()
            ->map->toPublicArray();

        return response()->json(['faqs' => $faqs]);
    }

    public function conversation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'guest_token' => ['nullable', 'uuid'],
        ]);

        $conversation = $this->support->openForMobile(
            $this->resolveUser($request),
            $data['guest_token'] ?? null,
            'mobile',
        );

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'guest_token' => $conversation->guest_token,
            ],
        ]);
    }

    public function messages(Request $request, SupportConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'guest_token' => ['nullable', 'uuid'],
            'since' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->support->authorizeMobile($conversation, $this->resolveUser($request), $data['guest_token'] ?? null);

        $since = (int) ($data['since'] ?? 0);
        $query = $conversation->messages();
        if ($since > 0) {
            $query->where('id', '>', $since);
        }

        return response()->json([
            'messages' => $query->get()->map->toPublicArray(),
        ]);
    }

    public function storeMessage(Request $request, SupportConversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'guest_token' => ['nullable', 'uuid'],
            'body' => ['required_without:faq_id', 'nullable', 'string', 'max:2000'],
            'faq_id' => ['nullable', 'integer', 'exists:support_faqs,id'],
        ]);

        $this->support->authorizeMobile($conversation, $this->resolveUser($request), $data['guest_token'] ?? null);

        if ($conversation->status !== SupportConversation::STATUS_OPEN) {
            return response()->json(['message' => 'This conversation is closed.'], 422);
        }

        if ($faqId = $data['faq_id'] ?? null) {
            $faq = SupportFaq::query()->active()->findOrFail($faqId);
            $message = $this->support->addCustomerMessage($conversation, '', $this->resolveUser($request), $faq);
        } else {
            $message = $this->support->addCustomerMessage(
                $conversation,
                (string) $data['body'],
                $this->resolveUser($request),
            );
        }

        return response()->json([
            'message' => $message->toPublicArray(),
        ], 201);
    }

    private function locale(Request $request): string
    {
        $locale = $request->string('locale')->trim()->toString();

        return in_array($locale, ['en', 'so'], true) ? $locale : 'en';
    }

    private function resolveUser(Request $request): ?User
    {
        $user = $request->user('sanctum');
        if ($user instanceof User) {
            return $user;
        }

        $token = $request->bearerToken();
        if (! $token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        return $accessToken?->tokenable instanceof User ? $accessToken->tokenable : null;
    }
}
