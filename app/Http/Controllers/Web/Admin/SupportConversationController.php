<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Services\SupportConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportConversationController extends Controller
{
    public function __construct(private SupportConversationService $support) {}

    public function index(Request $request): View
    {
        $query = SupportConversation::query()->with('user')->recent();

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($request->boolean('unread')) {
            $query->where(function ($q) {
                $q->whereNull('admin_read_at')
                    ->orWhereColumn('admin_read_at', '<', 'last_message_at');
            });
        }

        $conversations = $query->paginate(20)->withQueryString();

        return view('admin.support.conversations.index', compact('conversations'));
    }

    public function show(SupportConversation $conversation): View
    {
        $conversation->load(['user', 'messages.sender']);
        $conversation->markAdminRead();

        return view('admin.support.conversations.show', compact('conversation'));
    }

    public function reply(Request $request, SupportConversation $conversation): RedirectResponse
    {
        if ($conversation->status !== SupportConversation::STATUS_OPEN) {
            return back()->with('error', 'Conversation is closed.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $this->support->addAdminMessage($conversation, $request->user(), $data['body']);
        $conversation->markAdminRead();

        return back()->with('success', 'Reply sent.');
    }

    public function close(SupportConversation $conversation): RedirectResponse
    {
        $conversation->update(['status' => SupportConversation::STATUS_CLOSED]);

        return back()->with('success', 'Conversation closed.');
    }

    public function reopen(SupportConversation $conversation): RedirectResponse
    {
        $conversation->update(['status' => SupportConversation::STATUS_OPEN]);

        return back()->with('success', 'Conversation reopened.');
    }
}
