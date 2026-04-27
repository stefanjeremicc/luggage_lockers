<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadController extends Controller
{
    private const ALLOWED_FOLDERS = ['blog', 'pages', 'misc'];

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|image|max:5120',
            'folder' => 'nullable|string',
        ]);

        $folder = in_array($validated['folder'] ?? 'misc', self::ALLOWED_FOLDERS, true)
            ? $validated['folder']
            : 'misc';

        $file = $request->file('file');
        $name = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("uploads/{$folder}", $name, 'public');

        return response()->json([
            'url' => Storage::url($path),
            'path' => $path,
        ], 201);
    }
}
