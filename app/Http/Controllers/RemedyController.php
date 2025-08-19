<?php

namespace App\Http\Controllers;

use App\Models\Remedy;
use App\Http\Resources\RemedyResource;
use App\Http\Resources\RemedyIndexResource;
use App\Http\Controllers\Concerns\ChecksPlanAccess;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class RemedyController extends Controller
{
    use ChecksPlanAccess;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Remedy::with(['remedyType', 'bodySystem', 'diseaseRelation', 'reviews.user', 'remedyTypes', 'bodySystems', 'diseases']);

            // Filter by title/name
            if ($request->has('title') && $request->title) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            // Filter by disease ID (singular BC)
            if ($request->has('disease_id') && $request->disease_id) {
                $query->where('disease_id', $request->disease_id);
            }
            // New: filter by multiple disease IDs
            if ($request->filled('disease_ids')) {
                $ids = is_array($request->disease_ids) ? $request->disease_ids : [$request->disease_ids];
                $query->where(function ($q) use ($ids) {
                    $q->whereIn('disease_id', $ids)
                        ->orWhereHas('diseases', function ($qq) use ($ids) {
                            $qq->whereIn('diseases.id', $ids);
                        });
                });
            }

            // Filter by body system
            if ($request->has('body_system_id') && $request->body_system_id) {
                $query->where('body_system_id', $request->body_system_id);
            }
            if ($request->filled('body_system_ids')) {
                $ids = is_array($request->body_system_ids) ? $request->body_system_ids : [$request->body_system_ids];
                $query->where(function ($q) use ($ids) {
                    $q->whereIn('body_system_id', $ids)
                        ->orWhereHas('bodySystems', function ($qq) use ($ids) {
                            $qq->whereIn('body_systems.id', $ids);
                        });
                });
            }

            // Filter by remedy type
            if ($request->has('remedy_type_id') && $request->remedy_type_id) {
                $query->where('remedy_type_id', $request->remedy_type_id);
            }
            if ($request->filled('remedy_type_ids')) {
                $ids = is_array($request->remedy_type_ids) ? $request->remedy_type_ids : [$request->remedy_type_ids];
                $query->where(function ($q) use ($ids) {
                    $q->whereIn('remedy_type_id', $ids)
                        ->orWhereHas('remedyTypes', function ($qq) use ($ids) {
                            $qq->whereIn('remedy_types.id', $ids);
                        });
                });
            }

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Filter by visibility to plan
            if ($request->has('visible_to_plan')) {
                $query->where('visible_to_plan', $request->visible_to_plan);
            }

            // Search in description
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('disease', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            // Sort by field
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['title', 'disease', 'status', 'created_at', 'updated_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Limit max per page to 100
            
            $remedies = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => RemedyIndexResource::collection($remedies),
                'pagination' => [
                    'current_page' => $remedies->currentPage(),
                    'last_page' => $remedies->lastPage(),
                    'per_page' => $remedies->perPage(),
                    'total' => $remedies->total(),
                    'from' => $remedies->firstItem(),
                    'to' => $remedies->lastItem(),
                    'has_more_pages' => $remedies->hasMorePages(),
                ],
                'message' => 'Remedies retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedies',
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
            // Normalize singular *_id fields if arrays are sent
            $data = $request->all();
            foreach (['remedy_type', 'body_system', 'disease'] as $prefix) {
                $idKey = $prefix . '_id';
                $idsKey = $prefix . '_ids';
                if (isset($data[$idKey]) && is_array($data[$idKey])) {
                    $data[$idsKey] = $data[$idKey];
                    $data[$idKey] = $data[$idKey][0] ?? null;
                }
            }

            $validator = Validator::make($data, [
                'title' => 'required|string|max:255',
                'main_image_url' => 'nullable|url',
                'disease' => 'required|string|max:255',
                'disease_id' => 'nullable|exists:diseases,id',
                'remedy_type_id' => 'required|exists:remedy_types,id',
                'body_system_id' => 'required|exists:body_systems,id',
                'description' => 'required|string',
                'visible_to_plan' => 'required|string|in:all,skilled,master,rookie',
                'status' => 'sometimes|in:active,inactive',
                'ingredients' => 'nullable|array',
                'ingredients.*.image_url' => 'nullable|url',
                'ingredients.*.name' => 'required|string',
                'instructions' => 'nullable|array',
                'instructions.*.image_url' => 'nullable|url',
                'instructions.*.name' => 'required|string',
                'benefits' => 'nullable|array',
                'benefits.*.image_url' => 'nullable|url',
                'benefits.*.name' => 'required|string',
                'precautions' => 'nullable|array',
                'precautions.*.image_url' => 'nullable|url',
                'precautions.*.name' => 'required|string',
                'product_link' => 'nullable|url',
                // New optional arrays (BC):
                'remedy_type_ids' => 'nullable|array',
                'remedy_type_ids.*' => 'integer|exists:remedy_types,id',
                'body_system_ids' => 'nullable|array',
                'body_system_ids.*' => 'integer|exists:body_systems,id',
                'disease_ids' => 'nullable|array',
                'disease_ids.*' => 'integer|exists:diseases,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $remedy = Remedy::create([
                'title' => $data['title'] ?? null,
                'main_image_url' => $data['main_image_url'] ?? null,
                'disease' => $data['disease'] ?? null,
                'disease_id' => $data['disease_id'] ?? null,
                'remedy_type_id' => $data['remedy_type_id'] ?? null,
                'body_system_id' => $data['body_system_id'] ?? null,
                'description' => $data['description'] ?? null,
                'visible_to_plan' => $data['visible_to_plan'] ?? null,
                'status' => $data['status'] ?? Remedy::STATUS_ACTIVE,
                'ingredients' => $data['ingredients'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'benefits' => $data['benefits'] ?? null,
                'precautions' => $data['precautions'] ?? null,
                'product_link' => $data['product_link'] ?? null,
            ]);

            // Sync many-to-many arrays if provided (and keep single columns as fallback)
            $dirty = false;
            if (!empty($data['remedy_type_ids'])) {
                $ids = is_array($data['remedy_type_ids']) ? $data['remedy_type_ids'] : [$data['remedy_type_ids']];
                $remedy->remedyTypes()->sync($ids);
                $first = $ids[0] ?? null;
                if ($first && $remedy->remedy_type_id !== $first) {
                    $remedy->remedy_type_id = $first;
                    $dirty = true;
                }
            }
            if (!empty($data['body_system_ids'])) {
                $ids = is_array($data['body_system_ids']) ? $data['body_system_ids'] : [$data['body_system_ids']];
                $remedy->bodySystems()->sync($ids);
                $first = $ids[0] ?? null;
                if ($first && $remedy->body_system_id !== $first) {
                    $remedy->body_system_id = $first;
                    $dirty = true;
                }
            }
            if (!empty($data['disease_ids'])) {
                $ids = is_array($data['disease_ids']) ? $data['disease_ids'] : [$data['disease_ids']];
                $remedy->diseases()->sync($ids);
                $first = $ids[0] ?? null;
                if ($first && $remedy->disease_id !== $first) {
                    $remedy->disease_id = $first;
                    $dirty = true;
                }
            }
            if ($dirty) {
                $remedy->save();
            }

            $remedy->load(['remedyType', 'bodySystem', 'diseaseRelation', 'remedyTypes', 'bodySystems', 'diseases']);

            return response()->json([
                'success' => true,
                'data' => new RemedyResource($remedy),
                'message' => 'Remedy created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create remedy',
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
            $remedy = Remedy::with([
                'remedyType', 
                'bodySystem', 
                'diseaseRelation', 
                'reviews.user', 
                'reviews.reactions',
                'remedyTypes',
                'bodySystems',
                'diseases'
            ])->find($id);
            
            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            // Get related remedies from the same category with similar names
            $relatedRemedies = $this->getRelatedRemedies($remedy);

            // Targeted ads for this remedy
            $ads = \App\Models\Ad::active()->forPlacement(\App\Models\Ad::TYPE_REMEDY, (int)$remedy->id)->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => new RemedyResource($remedy),
                'related_remedies' => RemedyIndexResource::collection($relatedRemedies),
                'ads' => \App\Http\Resources\AdResource::collection($ads),
                'message' => 'Remedy retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mobile-only show with plan gating.
     */
    public function showForMobile(string $id): JsonResponse
    {
        try {
            $remedy = Remedy::with([
                'remedyType',
                'bodySystem',
                'diseaseRelation',
                'reviews.user',
                'reviews.reactions',
                'remedyTypes',
                'bodySystems',
                'diseases'
            ])->find($id);

            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            $requiredPlan = $remedy->visible_to_plan; // 'rookie'|'skilled'|'master'|'all'|null
            $user = auth('sanctum')->user();
            if (!$this->canAccessByPlan($user, $requiredPlan)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upgrade required to view this content',
                    'required_plan' => $this->normalizeRequiredPlan($requiredPlan) ?? 'rookie',
                    'user_plan' => $this->userEffectivePlan($user),
                ], 403);
            }

            $relatedRemedies = $this->getRelatedRemedies($remedy);
            $ads = \App\Models\Ad::active()->forPlacement(\App\Models\Ad::TYPE_REMEDY, (int)$remedy->id)->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => new RemedyResource($remedy),
                'related_remedies' => RemedyIndexResource::collection($relatedRemedies),
                'ads' => \App\Http\Resources\AdResource::collection($ads),
                'message' => 'Remedy retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show remedy for mobile dashboard with full relationship data.
     */
    public function showForDashboard(string $id): JsonResponse
    {
        try {
            $remedy = Remedy::with([
                'remedyType', 
                'bodySystem', 
                'diseaseRelation', 
                'reviews.user', 
                'reviews.reactions',
                'diseaseRelation',
                'remedyTypes',
                'bodySystems',
                'diseases'
            ])->find($id);
            
            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            // Get related remedies from the same category with similar names
            $relatedRemedies = $this->getRelatedRemedies($remedy);

            return response()->json([
                'success' => true,
                'data' => new \App\Http\Resources\RemedyDashboardResource($remedy),
                'message' => 'Remedy retrieved successfully for dashboard'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedy for dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get related remedies from the same category with similar names
     */
    private function getRelatedRemedies(Remedy $remedy): \Illuminate\Database\Eloquent\Collection
    {
        // Gather remedy type IDs from both single column and many-to-many
        $typeIds = [];
        if (!empty($remedy->remedy_type_id)) {
            $typeIds[] = $remedy->remedy_type_id;
        }
        try {
            $manyIds = $remedy->remedyTypes()->pluck('remedy_types.id')->toArray();
            $typeIds = array_values(array_unique(array_merge($typeIds, $manyIds)));
        } catch (\Throwable $e) {
            // ignore
        }

        $relatedRemedies = Remedy::where('id', '!=', $remedy->id)
            ->where('status', 'active')
            ->when(!empty($typeIds), function ($q) use ($typeIds) {
                $q->whereIn('remedy_type_id', $typeIds)
                  ->orWhereHas('remedyTypes', function ($qq) use ($typeIds) {
                      $qq->whereIn('remedy_types.id', $typeIds);
                  });
            })
            ->with(['remedyType', 'bodySystem', 'diseaseRelation', 'reviews.user', 'reviews.reactions', 'remedyTypes', 'bodySystems', 'diseases'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        // If we don't have enough remedies from the same category, add remedies with similar names
        if ($relatedRemedies->count() < 6) {
            $remainingCount = 6 - $relatedRemedies->count();
            
            // Get remedies with similar names (using LIKE for partial matching)
            $similarNameRemedies = Remedy::where('id', '!=', $remedy->id)
                ->where('id', 'not in', $relatedRemedies->pluck('id'))
                ->where('status', 'active')
                ->where(function($query) use ($remedy) {
                    $query->where('title', 'LIKE', '%' . $remedy->title . '%')
                          ->orWhere('disease', 'LIKE', '%' . $remedy->disease . '%')
                          ->orWhere('title', 'LIKE', '%' . $remedy->disease . '%')
                          ->orWhere('disease', 'LIKE', '%' . $remedy->title . '%');
                })
                ->with(['remedyType', 'bodySystem', 'diseaseRelation', 'reviews.user', 'reviews.reactions', 'remedyTypes', 'bodySystems', 'diseases'])
                ->orderBy('created_at', 'desc')
                ->limit($remainingCount)
                ->get();

            $relatedRemedies = $relatedRemedies->merge($similarNameRemedies);
        }

        return $relatedRemedies;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $remedy = Remedy::find($id);
            
            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            // Normalize singular *_id fields if arrays are sent
            $data = $request->all();
            foreach (['remedy_type', 'body_system', 'disease'] as $prefix) {
                $idKey = $prefix . '_id';
                $idsKey = $prefix . '_ids';
                if (isset($data[$idKey]) && is_array($data[$idKey])) {
                    $data[$idsKey] = $data[$idKey];
                    $data[$idKey] = $data[$idKey][0] ?? null;
                }
            }

            $validator = Validator::make($data, [
                'title' => 'sometimes|required|string|max:255',
                'main_image_url' => 'nullable|url',
                'disease' => 'sometimes|required|string|max:255',
                'disease_id' => 'nullable|exists:diseases,id',
                'remedy_type_id' => 'sometimes|required|exists:remedy_types,id',
                'body_system_id' => 'sometimes|required|exists:body_systems,id',
                'description' => 'sometimes|required|string',
                'visible_to_plan' => 'sometimes|required|string|in:all,skilled,master,rookie',
                'status' => 'sometimes|in:active,inactive',
                'ingredients' => 'nullable|array',
                'ingredients.*.image_url' => 'nullable|url',
                'ingredients.*.name' => 'required|string',
                'instructions' => 'nullable|array',
                'instructions.*.image_url' => 'nullable|url',
                'instructions.*.name' => 'required|string',
                'benefits' => 'nullable|array',
                'benefits.*.image_url' => 'nullable|url',
                'benefits.*.name' => 'required|string',
                'precautions' => 'nullable|array',
                'precautions.*.image_url' => 'nullable|url',
                'precautions.*.name' => 'required|string',
                'product_link' => 'nullable|url',
                // New optional arrays (BC):
                'remedy_type_ids' => 'nullable|array',
                'remedy_type_ids.*' => 'integer|exists:remedy_types,id',
                'body_system_ids' => 'nullable|array',
                'body_system_ids.*' => 'integer|exists:body_systems,id',
                'disease_ids' => 'nullable|array',
                'disease_ids.*' => 'integer|exists:diseases,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $remedy->update($data);

            // Sync many-to-many arrays if provided and keep single columns up-to-date
            $dirty = false;
            if (!empty($data['remedy_type_ids'])) {
                $ids = is_array($data['remedy_type_ids']) ? $data['remedy_type_ids'] : [$data['remedy_type_ids']];
                $remedy->remedyTypes()->sync($ids);
                $first = $ids[0] ?? null;
                if ($first && $remedy->remedy_type_id !== $first) {
                    $remedy->remedy_type_id = $first;
                    $dirty = true;
                }
            }
            if (!empty($data['body_system_ids'])) {
                $ids = is_array($data['body_system_ids']) ? $data['body_system_ids'] : [$data['body_system_ids']];
                $remedy->bodySystems()->sync($ids);
                $first = $ids[0] ?? null;
                if ($first && $remedy->body_system_id !== $first) {
                    $remedy->body_system_id = $first;
                    $dirty = true;
                }
            }
            if (!empty($data['disease_ids'])) {
                $ids = is_array($data['disease_ids']) ? $data['disease_ids'] : [$data['disease_ids']];
                $remedy->diseases()->sync($ids);
                $first = $ids[0] ?? null;
                if ($first && $remedy->disease_id !== $first) {
                    $remedy->disease_id = $first;
                    $dirty = true;
                }
            }
            if ($dirty) {
                $remedy->save();
            }

            $remedy->load(['remedyType', 'bodySystem', 'diseaseRelation', 'remedyTypes', 'bodySystems', 'diseases']);

            return response()->json([
                'success' => true,
                'data' => new RemedyResource($remedy),
                'message' => 'Remedy updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update remedy',
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
            $remedy = Remedy::find($id);
            
            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            $remedy->delete();

            return response()->json([
                'success' => true,
                'message' => 'Remedy deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete remedy',
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
            $remedy = Remedy::find($id);
            
            if (!$remedy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Remedy not found'
                ], 404);
            }

            $newStatus = $remedy->status === Remedy::STATUS_ACTIVE 
                ? Remedy::STATUS_INACTIVE 
                : Remedy::STATUS_ACTIVE;

            $remedy->update(['status' => $newStatus]);
            $remedy->load(['remedyType', 'bodySystem']);

            return response()->json([
                'success' => true,
                'data' => new RemedyResource($remedy),
                'message' => 'Remedy status toggled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle remedy status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get featured remedies for mobile app.
     */
    public function featured(): JsonResponse
    {
        try {
            $remedies = Remedy::with(['remedyType', 'bodySystem', 'remedyTypes', 'bodySystems'])
                ->where('status', 'active')
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => RemedyResource::collection($remedies),
                'message' => 'Featured remedies retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve featured remedies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get remedies by body system for mobile app.
     */
    public function byBodySystem(string $bodySystemId): JsonResponse
    {
        try {
            $remedies = Remedy::with(['remedyType', 'bodySystem', 'remedyTypes', 'bodySystems'])
                ->where('status', 'active')
                ->where(function ($q) use ($bodySystemId) {
                    $q->where('body_system_id', $bodySystemId)
                      ->orWhereHas('bodySystems', function ($qq) use ($bodySystemId) {
                          $qq->where('body_systems.id', $bodySystemId);
                      });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => RemedyResource::collection($remedies),
                'message' => 'Remedies by body system retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve remedies by body system',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
