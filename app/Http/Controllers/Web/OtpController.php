<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'purpose' => ['required', 'string', 'in:register,checkout,find_tickets'],
        ]);

        $result = $this->otp->send($data['phone'], $data['purpose']);

        return response()->json([
            'message' => $result['message'],
            'phone' => $result['phone'],
            'expires_in' => $result['expires_in'],
            'debug_code' => $result['debug_code'] ?? null,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'purpose' => ['required', 'string', 'in:register,checkout,find_tickets'],
            'otp' => ['required', 'string', 'max:12'],
        ]);

        $token = $this->otp->verify($data['phone'], $data['purpose'], $data['otp']);
        $phone = $this->otp->normalize($data['phone']);

        return response()->json([
            'message' => 'Phone confirmed.',
            'phone' => $phone,
            'otp_token' => $token,
            'purpose' => $data['purpose'],
        ]);
    }
}
