<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportFaq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportFaqController extends Controller
{
    public function index(Request $request): View
    {
        $query = SupportFaq::query()->ordered();

        if ($locale = $request->string('locale')->trim()->toString()) {
            $query->forLocale($locale);
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $faqs = $query->paginate(30)->withQueryString();

        return view('admin.support.faqs.index', compact('faqs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'in:en,so'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $maxOrder = (int) SupportFaq::query()
            ->where('locale', $data['locale'])
            ->max('sort_order');

        SupportFaq::query()->create([
            'locale' => $data['locale'],
            'question' => trim($data['question']),
            'answer' => trim($data['answer']),
            'sort_order' => $data['sort_order'] ?? ($maxOrder + 1),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'FAQ created.');
    }

    public function update(Request $request, SupportFaq $faq): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'in:en,so'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $faq->update([
            'locale' => $data['locale'],
            'question' => trim($data['question']),
            'answer' => trim($data['answer']),
            'sort_order' => $data['sort_order'] ?? $faq->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'FAQ updated.');
    }

    public function toggle(SupportFaq $faq): RedirectResponse
    {
        $faq->update(['is_active' => ! $faq->is_active]);

        return back()->with('success', $faq->is_active ? 'FAQ activated.' : 'FAQ deactivated.');
    }

    public function destroy(SupportFaq $faq): RedirectResponse
    {
        $faq->delete();

        return back()->with('success', 'FAQ deleted.');
    }
}
