<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InvitationDesign;
use App\Models\InvitationDesignField;
use App\Support\PublicUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvitationDesignController extends Controller
{
    public function index(Request $request): View
    {
        $query = InvitationDesign::query()->ordered()->with('category')->withCount(['fields', 'events']);

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        if ($request->filled('category')) {
            $query->where('private_event_category_id', $request->integer('category'));
        }

        $designs = $query->paginate(20)->withQueryString();
        $categories = $this->privateCategoryOptions(false);

        return view('admin.invitation-designs.index', compact('designs', 'categories'));
    }

    public function create(): View
    {
        return view('admin.invitation-designs.form', [
            'design' => new InvitationDesign([
                'tier' => 'standard',
                'render_mode' => 'overlay',
                'is_active' => true,
                'accent' => '#705898',
                'card_bg' => '#faf7fc',
                'text_color' => '#3d3348',
                'muted_color' => '#6b6280',
                'border_color' => '#c5a059',
            ]),
            'categories' => $this->privateCategoryOptions(true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['name'] = $this->autoName((int) $data['private_event_category_id']);
        $data['description'] = null;
        $data['graphic_path'] = $this->storeGraphic($request->file('graphic'));
        $data['thumbnail_path'] = $this->storeGraphic($request->file('thumbnail'), 'thumbs') ?: $data['graphic_path'];
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? ((int) InvitationDesign::query()->max('sort_order') + 1);

        $design = InvitationDesign::query()->create($data);

        return redirect()
            ->route('admin.invitation-designs.edit', $design)
            ->with('success', 'Design created. Add text fields customers will fill.');
    }

    public function edit(InvitationDesign $invitationDesign): View
    {
        $invitationDesign->load('fields');

        return view('admin.invitation-designs.form', [
            'design' => $invitationDesign,
            'categories' => $this->privateCategoryOptions(false),
        ]);
    }

    public function update(Request $request, InvitationDesign $invitationDesign): RedirectResponse
    {
        $data = $this->validated($request, $invitationDesign);
        unset($data['name'], $data['description']);
        if ($file = $request->file('graphic')) {
            $data['graphic_path'] = $this->storeGraphic($file);
            if (! $request->file('thumbnail')) {
                $data['thumbnail_path'] = $data['graphic_path'];
            }
        }
        if ($file = $request->file('thumbnail')) {
            $data['thumbnail_path'] = $this->storeGraphic($file, 'thumbs');
        }
        $data['is_active'] = $request->boolean('is_active');

        $invitationDesign->update($data);

        return back()->with('success', 'Design updated.');
    }

    public function destroy(InvitationDesign $invitationDesign): RedirectResponse
    {
        if ($invitationDesign->events()->exists()) {
            return back()->with('error', 'Cannot delete — events use this design. Deactivate it instead.');
        }

        $invitationDesign->delete();

        return redirect()
            ->route('admin.invitation-designs.index')
            ->with('success', 'Design deleted.');
    }

    public function toggle(InvitationDesign $invitationDesign): RedirectResponse
    {
        $invitationDesign->update(['is_active' => ! $invitationDesign->is_active]);

        return back()->with('success', $invitationDesign->is_active ? 'Design activated.' : 'Design deactivated.');
    }

    public function storeField(Request $request, InvitationDesign $invitationDesign): RedirectResponse
    {
        $data = $this->validatedField($request, $invitationDesign);
        $data['invitation_design_id'] = $invitationDesign->id;
        $data['sort_order'] = $data['sort_order'] ?? ((int) $invitationDesign->fields()->max('sort_order') + 1);
        $data['is_required'] = $request->boolean('is_required');
        $data['maps_to_couple'] = $request->boolean('maps_to_couple');
        $data['show_on_card'] = $request->boolean('show_on_card', true);

        InvitationDesignField::query()->create($data);

        return back()->with('success', 'Field added.');
    }

    public function updateField(Request $request, InvitationDesign $invitationDesign, InvitationDesignField $field): RedirectResponse|JsonResponse
    {
        abort_unless($field->invitation_design_id === $invitationDesign->id, 404);

        $data = $this->validatedField($request, $invitationDesign, $field);
        $data['is_required'] = $request->boolean('is_required');
        $data['maps_to_couple'] = $request->boolean('maps_to_couple');
        $data['show_on_card'] = $request->boolean('show_on_card');

        $field->update($data);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'field' => $field->fresh()->toCatalogArray(),
            ]);
        }

        return back()->with('success', 'Field updated.');
    }

    public function destroyField(InvitationDesign $invitationDesign, InvitationDesignField $field): RedirectResponse
    {
        abort_unless($field->invitation_design_id === $invitationDesign->id, 404);
        $field->delete();

        return back()->with('success', 'Field removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?InvitationDesign $design = null): array
    {
        return $request->validate([
            'private_event_category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($q) => $q->where('parent_id', Category::privateRoot()?->id ?? 0)
                ),
            ],
            'tier' => ['required', Rule::in(['standard', 'premium'])],
            'ticket_price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'premium_surcharge' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'render_mode' => ['required', Rule::in(['blade', 'overlay'])],
            'blade_key' => ['nullable', 'string', 'max:80'],
            'accent' => ['nullable', 'string', 'max:20'],
            'accent_soft' => ['nullable', 'string', 'max:20'],
            'header_from' => ['nullable', 'string', 'max:20'],
            'header_to' => ['nullable', 'string', 'max:20'],
            'card_bg' => ['nullable', 'string', 'max:20'],
            'text_color' => ['nullable', 'string', 'max:20'],
            'muted_color' => ['nullable', 'string', 'max:20'],
            'border_color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'graphic' => [$design ? 'nullable' : 'required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'],
            'thumbnail' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:4096'],
        ], [
            'graphic.uploaded' => 'The graphic failed to upload. Use a PNG/JPG/WebP under 10MB and try again.',
            'graphic.mimetypes' => 'The graphic must be a PNG, JPG, or WebP image.',
            'graphic.max' => 'The graphic may not be greater than 10MB.',
            'thumbnail.uploaded' => 'The thumbnail failed to upload. Use a PNG/JPG/WebP under 4MB.',
            'thumbnail.mimetypes' => 'The thumbnail must be a PNG, JPG, or WebP image.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedField(Request $request, InvitationDesign $design, ?InvitationDesignField $field = null): array
    {
        $rawKey = (string) $request->input('field_key', '');
        $normalizedKey = strtolower(trim($rawKey));
        $normalizedKey = preg_replace('/[^a-z0-9]+/', '_', $normalizedKey) ?? '';
        $normalizedKey = trim($normalizedKey, '_');
        if ($normalizedKey === '' && $request->filled('label')) {
            $fromLabel = strtolower(trim((string) $request->input('label')));
            $fromLabel = preg_replace('/[^a-z0-9]+/', '_', $fromLabel) ?? '';
            $normalizedKey = trim($fromLabel, '_');
        }
        $request->merge(['field_key' => $normalizedKey]);

        return $request->validate([
            'field_key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('invitation_design_fields', 'field_key')
                    ->where(fn ($q) => $q->where('invitation_design_id', $design->id))
                    ->ignore($field?->id),
            ],
            'label' => ['required', 'string', 'max:120'],
            'field_type' => ['required', Rule::in([
                'text',
                'textarea',
                'qr',
                ...\App\Support\InvitationDateFields::TYPES,
            ])],
            'placeholder' => ['nullable', 'string', 'max:180'],
            'default_text' => ['nullable', 'string', 'max:255'],
            'pos_x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pos_y' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'box_width' => ['nullable', 'numeric', 'min:5', 'max:100'],
            'font_size' => ['nullable', 'integer', 'min:8', 'max:72'],
            'font_family' => ['nullable', 'string', 'max:80'],
            'font_weight' => ['nullable', 'string', 'max:30'],
            'font_style' => ['nullable', 'string', 'max:30'],
            'color' => ['nullable', 'string', 'max:20'],
            'text_align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Category>
     */
    private function privateCategoryOptions(bool $activeOnly = false)
    {
        $privateId = Category::privateRoot()?->id;
        if (! $privateId) {
            return collect();
        }

        $q = Category::query()->childrenOf($privateId)->ordered();
        if ($activeOnly) {
            $q->active();
        }

        return $q->get(['id', 'name']);
    }

    private function autoName(int $categoryId): string
    {
        $category = Category::query()->find($categoryId);
        $base = $category?->name ?: 'Design';
        $n = (int) InvitationDesign::query()->where('private_event_category_id', $categoryId)->count() + 1;

        return $base.' '.$n;
    }

    private function storeGraphic(?UploadedFile $file, string $subdir = ''): ?string
    {
        if (! $file) {
            return null;
        }

        if (! $file->isValid()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'graphic' => ['The graphic failed to upload ('.$file->getErrorMessage().'). Try a smaller PNG/JPG under 10MB.'],
            ]);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = match ($file->getMimeType()) {
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                default => 'png',
            };
        }

        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'design';
        $name = $base.'-'.Str::random(6).'.'.$ext;
        $directory = 'images/invitations'.($subdir ? '/'.$subdir : '');

        try {
            return PublicUpload::store($file, $directory, $name);
        } catch (\Throwable $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'graphic' => ['The image could not be saved on the server. '.$e->getMessage()],
            ]);
        }
    }
}
