<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:10', 'max:30'],
            'shop_name' => ['required', 'string', 'min:2', 'max:255'],
            'shop_type' => ['required', 'in:tailor,boutique,both'],
            'preferred_date' => ['nullable', 'date'],
            'preferred_time' => ['nullable', 'string', 'max:10'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $appointment = Appointment::create([
            ...$validated,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Demo request received successfully.',
            'appointment' => $appointment,
        ], 201);
    }
}
