<?php

namespace App\Http\Controllers;

use App\Models\ContactUs;
use App\Http\Resources\ContactUsResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ContactUs::query();

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }
            // Filter by name
            if ($request->has('name') && $request->name) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }
            // Filter by email
            if ($request->has('email') && $request->email) {
                $query->where('email', 'like', '%' . $request->email . '%');
            }
            // Filter by subject
            if ($request->has('subject') && $request->subject) {
                $query->where('subject', 'like', '%' . $request->subject . '%');
            }
            // General search
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('subject', 'like', '%' . $search . '%')
                      ->orWhere('message', 'like', '%' . $search . '%');
                });
            }
            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            if (in_array($sortBy, ['name', 'email', 'subject', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }
            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100);
            $contacts = $query->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => ContactUsResource::collection($contacts),
                'pagination' => [
                    'current_page' => $contacts->currentPage(),
                    'last_page' => $contacts->lastPage(),
                    'per_page' => $contacts->perPage(),
                    'total' => $contacts->total(),
                    'from' => $contacts->firstItem(),
                    'to' => $contacts->lastItem(),
                    'has_more_pages' => $contacts->hasMorePages(),
                ],
                'message' => 'Contact messages retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contact messages',
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
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'nullable|string|max:20',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $contact = ContactUs::create($request->only(['name', 'email', 'phone', 'subject', 'message']));
            return response()->json([
                'success' => true,
                'data' => new ContactUsResource($contact),
                'message' => 'Contact message submitted successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit contact message',
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
            $contact = ContactUs::find($id);
            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact message not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'data' => new ContactUsResource($contact),
                'message' => 'Contact message retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contact message',
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
            $contact = ContactUs::find($id);
            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact message not found'
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email',
                'phone' => 'nullable|string|max:20',
                'subject' => 'sometimes|required|string|max:255',
                'message' => 'sometimes|required|string',
                'status' => 'sometimes|in:new,read,archived',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $contact->update($request->all());
            return response()->json([
                'success' => true,
                'data' => new ContactUsResource($contact),
                'message' => 'Contact message updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contact message',
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
            $contact = ContactUs::find($id);
            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact message not found'
                ], 404);
            }
            $contact->delete();
            return response()->json([
                'success' => true,
                'message' => 'Contact message deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete contact message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change the status of the specified contact message.
     */
    public function changeStatus(Request $request, string $id): JsonResponse
    {
        try {
            $contact = ContactUs::find($id);
            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact message not found'
                ], 404);
            }
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:new,read,archived',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $contact->update(['status' => $request->status]);
            return response()->json([
                'success' => true,
                'data' => new ContactUsResource($contact),
                'message' => 'Contact message status updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contact message status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 