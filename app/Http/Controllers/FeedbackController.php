<?php

namespace App\Http\Controllers;

use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = Feedback::query();

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        if (in_array($sortBy, ['id', 'rating', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = (int) $request->get('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $feedback = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Feedback fetched successfully',
            'data' => FeedbackResource::collection($feedback),
            'pagination' => [
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage(),
                'per_page' => $feedback->perPage(),
                'total' => $feedback->total(),
                'from' => $feedback->firstItem(),
                'to' => $feedback->lastItem(),
                'has_more_pages' => $feedback->hasMorePages(),
            ],
        ], 200);
    }
}
