<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20);

        return view('backend.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return view('backend.faqs.create', [
            'faq' => new Faq(),
        ]);
    }

    public function store(Request $request)
    {
        Faq::create($this->validated($request));

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'FAQ created successfully.');
    }

    public function edit(Faq $faq)
    {
        return view('backend.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->validated($request));

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'FAQ deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
