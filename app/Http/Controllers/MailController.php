<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmployerRoleRequest;
use Illuminate\Support\Facades\Auth;

class MailController extends Controller
{
    /**
     * Handle the user's request for an employer role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestEmployerRole(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:10',
        ]);

        $user = Auth::user();
        $message = $request->input('message');
        $adminEmail = 'no-reply@jobboard.com';

        try {
            Mail::to($adminEmail)->send(new EmployerRoleRequest($user, $message));
            
            return response()->json(['message' => 'Your request has been sent! We will review it shortly.']);

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Mail sending failed: ' . $e->getMessage());

            return response()->json(['message' => 'There was an error sending your request. Please try again later.'], 500);
        }
    }
}