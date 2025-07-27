<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Http\Resources\FaqResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Faq::query();

            // Filter by question
            if ($request->has('question') && $request->question) {
                $query->where('question', 'like', '%' . $request->question . '%');
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // General search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('question', 'like', '%' . $search . '%')
                      ->orWhere('answer', 'like', '%' . $search . '%');
                });
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'order');
            $sortOrder = $request->get('sort_order', 'asc');
            if (in_array($sortBy, ['order', 'question', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);
            $faqs = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => FaqResource::collection($faqs),
                'pagination' => [
                    'current_page' => $faqs->currentPage(),
                    'last_page' => $faqs->lastPage(),
                    'per_page' => $faqs->perPage(),
                    'total' => $faqs->total(),
                    'from' => $faqs->firstItem(),
                    'to' => $faqs->lastItem(),
                    'has_more_pages' => $faqs->hasMorePages(),
                ],
                'message' => 'FAQs retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve FAQs',
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
                'question' => 'required|string|max:255',
                'answer' => 'required|string',
                'order' => 'nullable|integer',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $faq = Faq::create([
                'question' => $request->question,
                'answer' => $request->answer,
                'order' => $request->order ?? 0,
                'status' => $request->status ?? Faq::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'data' => new FaqResource($faq),
                'message' => 'FAQ created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create FAQ',
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
            $faq = Faq::find($id);
            if (!$faq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new FaqResource($faq),
                'message' => 'FAQ retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve FAQ',
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
            $faq = Faq::find($id);
            if (!$faq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'question' => 'sometimes|required|string|max:255',
                'answer' => 'sometimes|required|string',
                'order' => 'nullable|integer',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $faq->update($request->all());

            return response()->json([
                'success' => true,
                'data' => new FaqResource($faq),
                'message' => 'FAQ updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update FAQ',
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
            $faq = Faq::find($id);
            if (!$faq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ not found'
                ], 404);
            }
            $faq->delete();
            return response()->json([
                'success' => true,
                'message' => 'FAQ deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete FAQ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $faq = Faq::find($id);
            if (!$faq) {
                return response()->json([
                    'success' => false,
                    'message' => 'FAQ not found'
                ], 404);
            }
            $newStatus = $faq->status === Faq::STATUS_ACTIVE 
                ? Faq::STATUS_INACTIVE 
                : Faq::STATUS_ACTIVE;
            $faq->update(['status' => $newStatus]);
            return response()->json([
                'success' => true,
                'data' => new FaqResource($faq),
                'message' => 'FAQ status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle FAQ status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 