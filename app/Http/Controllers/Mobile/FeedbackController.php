<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
	/**
	 * Store feedback from mobile app users or guests.
	 */
	public function store(Request $request): JsonResponse
	{
		try {
			$validator = Validator::make($request->all(), [
				'rating' => 'required',
				'message' => 'nullable|string',
				'device' => 'nullable|string|max:255',
				'app_version' => 'nullable|string|max:50',
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'message' => 'Validation failed',
					'errors' => $validator->errors()
				], 422);
			}

			$payload = $request->only(['rating', 'message', 'device', 'app_version']);
			$payload['user_id'] = optional($request->user())->id;

			$feedback = Feedback::create($payload);

			return response()->json([
				'success' => true,
				'data' => new FeedbackResource($feedback),
				'message' => 'Feedback submitted successfully'
			], 201);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to submit feedback',
				'error' => $e->getMessage()
			], 500);
		}
	}
}


