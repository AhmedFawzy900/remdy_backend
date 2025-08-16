<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Http\Resources\AboutResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AboutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $about = About::first();

            if (!$about) {
                return response()->json([
                    'success' => false,
                    'message' => 'About data not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new AboutResource($about),
                'message' => 'About data retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve about data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'mainDescription' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $existingAbout = About::first();

            if ($existingAbout) {
                $existingAbout->update([
                    'main_description' => $request->mainDescription,
                ]);
                $about = $existingAbout;
                $statusCode = 200;
                $message = 'About data updated successfully';
            } else {
                $about = About::create([
                    'main_description' => $request->mainDescription,
                ]);
                $statusCode = 201;
                $message = 'About data created successfully';
            }

            return response()->json([
                'success' => true,
                'data' => new AboutResource($about),
                'message' => $message
            ], $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create about data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $about = About::first();
            if (!$about) {
                return response()->json([
                    'success' => false,
                    'message' => 'About data not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new AboutResource($about),
                'message' => 'About data retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve about data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $about = About::first();

            $validator = Validator::make($request->all(), [
                'mainDescription' => 'sometimes|required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($about) {
                $about->update([
                    'main_description' => $request->mainDescription ?? $about->main_description,
                ]);
                $message = 'About data updated successfully';
                $statusCode = 200;
            } else {
                // Create if not exists
                if (!$request->has('mainDescription')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'mainDescription is required to create about data'
                    ], 422);
                }
                $about = About::create([
                    'main_description' => $request->mainDescription,
                ]);
                $message = 'About data created successfully';
                $statusCode = 201;
            }

            return response()->json([
                'success' => true,
                'data' => new AboutResource($about),
                'message' => $message
            ], $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update about data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $about = About::first();
            if (!$about) {
                return response()->json([
                    'success' => false,
                    'message' => 'About data not found'
                ], 404);
            }
            $about->delete();
            return response()->json([
                'success' => true,
                'message' => 'About data deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete about data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 