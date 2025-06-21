<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{
    private $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send a custom WhatsApp message
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string|max:1000',
            'media_url' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate phone number
        if (!$this->whatsappService->validatePhoneNumber($request->phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format'
            ], 422);
        }

        $options = [];
        if ($request->has('media_url')) {
            $options['media_url'] = $request->media_url;
        }

        $result = $this->whatsappService->sendMessage(
            $request->phone,
            $request->message,
            $options
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp message sent successfully',
                'data' => $result
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send WhatsApp message',
            'error' => $result['error']
        ], 500);
    }
} 