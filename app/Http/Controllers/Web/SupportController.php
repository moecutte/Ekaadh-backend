<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportFaq;
use App\Models\SupportMessage;
use App\Services\SupportConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $conversation = $this->support->openForWeb($request->user(), $request, 'web');

        return response()->json([
            'conversation' => $this->conversationPayload($conversation),
        ]);
    }

    public function messages(Request $request, SupportConversation $conversation): JsonResponse
    {
        $this->support->authorizeWeb($conversation, $request->user(), $request);

        $since = $request->integer('since');
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
        $this->support->authorizeWeb($conversation, $request->user(), $request);

        if ($conversation->status !== SupportConversation::STATUS_OPEN) {
            return response()->json(['message' => 'This conversation is closed.'], 422);
        }

        $data = $request->validate([
            'body' => ['required_without:faq_id', 'nullable', 'string', 'max:2000'],
            'faq_id' => ['nullable', 'integer', 'exists:support_faqs,id'],
        ]);

        if ($faqId = $data['faq_id'] ?? null) {
            $faq = SupportFaq::query()->active()->findOrFail($faqId);
            $message = $this->support->addCustomerMessage($conversation, '', $request->user(), $faq);
        } else {
            $message = $this->support->addCustomerMessage(
                $conversation,
                (string) $data['body'],
                $request->user(),
            );
        }

        return response()->json([
            'message' => $message->toPublicArray(),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationPayload(SupportConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
        ];
    }

    private function locale(Request $request): string
    {
        $locale = $request->string('locale')->trim()->toString();

        return in_array($locale, ['en', 'so'], true) ? $locale : app()->getLocale();
    }
}
