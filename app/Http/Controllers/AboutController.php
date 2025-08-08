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
            $query = About::query();

            // Filter by main_description
            if ($request->has('main_description') && $request->main_description) {
                $query->where('main_description', 'like', '%' . $request->main_description . '%');
            }

            // General search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('main_description', 'like', '%' . $search . '%');
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['main_description', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Limit max per page to 100
            
            $abouts = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => AboutResource::collection($abouts),
                'pagination' => [
                    'current_page' => $abouts->currentPage(),
                    'last_page' => $abouts->lastPage(),
                    'per_page' => $abouts->perPage(),
                    'total' => $abouts->total(),
                    'from' => $abouts->firstItem(),
                    'to' => $abouts->lastItem(),
                    'has_more_pages' => $abouts->hasMorePages(),
                ],
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

            $about = About::create([
                'main_description' => $request->mainDescription,
            ]);

            return response()->json([
                'success' => true,
                'data' => new AboutResource($about),
                'message' => 'About data created successfully'
            ], 201);
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
            $about = About::find($id);
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
            $about = About::find($id);
            if (!$about) {
                return response()->json([
                    'success' => false,
                    'message' => 'About data not found'
                ], 404);
            }

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

            $about->update([
                'main_description' => $request->mainDescription ?? $about->main_description,
            ]);

            return response()->json([
                'success' => true,
                'data' => new AboutResource($about),
                'message' => 'About data updated successfully'
            ], 200);
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
            $about = About::find($id);
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