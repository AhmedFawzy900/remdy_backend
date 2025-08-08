<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Http\Resources\InstructorResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class InstructorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Instructor::with(['courses']);

            // Filter by name
            if ($request->has('name') && $request->name) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Filter by specialization
            if ($request->has('specialization') && $request->specialization) {
                $query->where('specialization', 'like', '%' . $request->specialization . '%');
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // General search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhere('specialization', 'like', '%' . $search . '%')
                      ->orWhere('bio', 'like', '%' . $search . '%');
                });
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            if (in_array($sortBy, ['name', 'specialization', 'experience_years', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Limit max per page to 100
            $instructors = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => InstructorResource::collection($instructors),
                'pagination' => [
                    'current_page' => $instructors->currentPage(),
                    'last_page' => $instructors->lastPage(),
                    'per_page' => $instructors->perPage(),
                    'total' => $instructors->total(),
                    'from' => $instructors->firstItem(),
                    'to' => $instructors->lastItem(),
                    'has_more_pages' => $instructors->hasMorePages(),
                ],
                'message' => 'Instructors retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve instructors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|url',
                'specialization' => 'nullable|string|max:255',
                'experience_years' => 'nullable|integer|min:0',
                'bio' => 'nullable|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $instructor = Instructor::create([
                'name' => $request->name,
                'description' => $request->description,
                'image' => $request->image,
                'specialization' => $request->specialization,
                'experience_years' => $request->experience_years,
                'bio' => $request->bio,
                'status' => $request->status ?? Instructor::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'data' => new InstructorResource($instructor),
                'message' => 'Instructor created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create instructor',
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
            $instructor = Instructor::with(['courses'])->find($id);
            
            if (!$instructor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instructor not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new InstructorResource($instructor),
                'message' => 'Instructor retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve instructor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $instructor = Instructor::find($id);
            
            if (!$instructor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instructor not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|url',
                'specialization' => 'nullable|string|max:255',
                'experience_years' => 'nullable|integer|min:0',
                'bio' => 'nullable|string',
                'status' => 'sometimes|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $instructor->update($request->all());

            return response()->json([
                'success' => true,
                'data' => new InstructorResource($instructor),
                'message' => 'Instructor updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update instructor',
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
            $instructor = Instructor::find($id);
            
            if (!$instructor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instructor not found'
                ], 404);
            }

            $instructor->delete();

            return response()->json([
                'success' => true,
                'message' => 'Instructor deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete instructor',
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
            $instructor = Instructor::find($id);
            
            if (!$instructor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instructor not found'
                ], 404);
            }

            $newStatus = $instructor->status === Instructor::STATUS_ACTIVE 
                ? Instructor::STATUS_INACTIVE 
                : Instructor::STATUS_ACTIVE;

            $instructor->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'data' => new InstructorResource($instructor),
                'message' => 'Instructor status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle instructor status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active instructors for selection in course creation.
     */
    public function forSelection(): JsonResponse
    {
        try {
            $instructors = Instructor::where('status', 'active')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => InstructorResource::collection($instructors),
                'message' => 'Active instructors retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active instructors',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
