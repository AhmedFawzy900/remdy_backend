<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;

class DeviceTokensController extends Controller
{
    public function updateToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'device' => 'required',
        ]);

        $user = auth('sanctum')->user();

        if ($user) {
            // Handle authenticated user case

            // Check if the token exists in the guests table and delete it
            Guest::where('token', $request->token)->delete();

            // Update or create the token in the deviceTokens table
            $exists = $user->deviceTokens()->where('token',
                '=',
                $request->token
            )->exists();
            if (!$exists) {
                $user->deviceTokens()->create([
                    'device' => $request->device,
                    'token' => $request->token,
                ]);
            } else {
                $user->deviceTokens()->where('token', '=', $request->token)->update(['device' => $request->device]);
            }
        } else {
            // Handle guest case
            $guest = Guest::where('token', '=', $request->token)->first();
            if (!$guest) {
                Guest::create([
                    'token' => $request->token,
                    'device' => $request->device,
                ]);
            } else {
                $guest->update(['device' => $request->device]);
            }
        }

        return response()->json([
            'status' => true,
            "message" => "Device token updated successfully"
        ]);
    }
}
