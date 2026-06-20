<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Http\Request;

class FormSubmissionController extends Controller
{
    public function index(Request $request, FormDefinition $form)
    {
        $submissions = $form->submissions()
            ->when($request->filter === 'unread', fn ($q) => $q->where('is_read', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('backend.form-submissions.index', compact('form', 'submissions'));
    }

    public function markRead(FormSubmission $submission)
    {
        $submission->update(['is_read' => ! $submission->is_read]);

        return back()->with('success', $submission->is_read ? 'Marked as read.' : 'Marked as unread.');
    }

    public function destroy(FormSubmission $submission)
    {
        $form = $submission->form;
        $submission->delete();

        return redirect()
            ->route('admin.forms.submissions.index', $form)
            ->with('success', 'Submission deleted.');
    }
}
