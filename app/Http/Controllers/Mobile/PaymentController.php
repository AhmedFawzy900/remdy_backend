<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function createPaymentIntent(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();

            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'sandbox' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $course = Course::find($request->course_id);

            // Check if already purchased
            $existingPurchase = CoursePurchase::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->where('status', 'completed')
                ->first();

            if ($existingPurchase) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already purchased this course',
                ], 400);
            }
            if ($request->sandbox) {
                Stripe::setApiKey(config('services.stripe.test_secret'));
            } else {
                Stripe::setApiKey(config('services.stripe.secret'));
            }

            $amountInCents = (int) round(((float) $course->price) * 100);

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'course_id' => (string) $course->id,
                ],
            ]);

            return response()->json([
                'success' => true,
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
                'amount' => $amountInCents,
                'currency' => 'usd',
                'payment_method_types' => ['card'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment intent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmPayment(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();

            $validator = Validator::make($request->all(), [
                'payment_intent_id' => 'required|string',
                'sandbox' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            if ($request->sandbox) {
                Stripe::setApiKey(config('services.stripe.test_secret'));
            } else {
                Stripe::setApiKey(config('services.stripe.secret'));
            }

            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if (!isset($paymentIntent->status) || $paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has not succeeded',
                    'status' => $paymentIntent->status ?? null,
                ], 400);
            }

            $metadata = $paymentIntent->metadata ?? [];
            $courseIdFromMetadata = isset($metadata['course_id']) ? (int) $metadata['course_id'] : null;
            $userIdFromMetadata = isset($metadata['user_id']) ? (int) $metadata['user_id'] : null;

            if (!$courseIdFromMetadata) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found in payment metadata',
                ], 400);
            }

            if ($userIdFromMetadata && $userIdFromMetadata !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment user does not match the authenticated user',
                ], 403);
            }

            $course = Course::find($courseIdFromMetadata);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found',
                ], 404);
            }

            // Verify paid amount matches current course price
            $expectedAmountInCents = (int) round(((float) $course->price) * 100);
            $paidAmountInCents = (int) ($paymentIntent->amount_received ?? $paymentIntent->amount ?? 0);
            if ($paidAmountInCents < $expectedAmountInCents) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paid amount does not match the course price',
                ], 400);
            }

            // Create or update purchase as completed
            $purchase = CoursePurchase::updateOrCreate([
                'user_id' => $user->id,
                'course_id' => $course->id,
            ], [
                'payment_method' => 'stripe_card',
                'payment_token' => $paymentIntent->id,
                'amount_paid' => $course->price,
                'status' => 'completed',
                'purchased_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Course purchased successfully',
                'data' => [
                    'purchase_id' => $purchase->id,
                    'course_id' => $course->id,
                    'amount_paid' => $purchase->amount_paid,
                    'purchased_at' => $purchase->purchased_at,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
