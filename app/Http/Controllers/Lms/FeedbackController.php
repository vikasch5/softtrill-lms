<?php

namespace App\Http\Controllers\Lms;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    public function feedbackList()
    {
        $feedbacks = Feedback::with('parent')
            ->latest('id')
            ->paginate(20);

        return view('lms.pages.feedback-list', compact('feedbacks'));
    }

    public function feedbackAdd($id = null)
    {
        $feedback = null;

        if ($id) {
            $feedback = Feedback::findOrFail($id);
        }

        $parents = Feedback::whereNull('parent_id')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        return view('lms.pages.feedback-add', compact(
            'feedback',
            'parents'
        ));
    }

    public function feedbackStoreOrUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'parent_id' => 'nullable|exists:feedbacks,id',
            'status' => 'required|boolean',
        ]);

        Feedback::updateOrCreate(
            [
                'id' => $request->feedback_id
            ],
            [
                'tenant_id' => auth()->user()->tenant_id,
                'parent_id' => $request->parent_id,
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'status' => $request->status,
            ]
        );

        $msg = $request->feedback_id
            ? 'Feedback updated successfully'
            : 'Feedback created successfully';

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    public function feedbackDelete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:feedbacks,id',
        ]);

        $feedback = Feedback::findOrFail($request->id);

        $feedback->delete();

        return response()->json([
            'status' => true,
            'message' => 'Feedback deleted successfully'
        ]);
    }

    public function subFeedbacks($feedbackId)
    {
        $subFeedbacks = Feedback::where('parent_id', $feedbackId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subFeedbacks);
    }
}
