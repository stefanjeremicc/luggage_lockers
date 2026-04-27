<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'email' => 'nullable|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'text' => 'required|string|min:10|max:1000',
            'website' => 'nullable|max:0', // honeypot
        ]);

        Review::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'rating' => $validated['rating'],
            'text' => $validated['text'],
            'source' => 'site',
            'status' => 'pending',
            'is_active' => false,
            'avatar_letter' => mb_strtoupper(mb_substr($validated['name'], 0, 1)),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => __('Thank you! Your review will appear after a quick check.')]);
        }

        return back()->with('review_submitted', true);
    }
}
