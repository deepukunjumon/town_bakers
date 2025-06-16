<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\TestMail;

class MailController extends Controller
{
    public function testMail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'cc' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $toEmails = array_map('trim', explode(',', $request->to));
        $ccEmails = $request->cc ? array_map('trim', explode(',', $request->cc)) : [];

        // Validate each email
        $allEmails = array_merge($toEmails, $ccEmails);
        $emailValidator = Validator::make(
            ['emails' => $allEmails],
            ['emails.*' => 'required|email']
        );

        if ($emailValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address found',
                'errors' => $emailValidator->errors()
            ], 422);
        }

        try {
            $mail = new TestMail();
            $message = Mail::to($toEmails);

            if (!empty($ccEmails)) {
                $message->cc($ccEmails);
            }

            $message->send($mail);

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
