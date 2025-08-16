<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Handle image upload and return the public URL.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|max:5120', // max 5MB
        ]);

        $file = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $destinationPath = public_path('uploads/images');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        $file->move($destinationPath, $filename);
        $url = url('uploads/images/' . $filename);

        return response()->json([
            'success' => true,
            'url' => $url,
            'message' => 'Image uploaded successfully',
        ]);
    }
} 