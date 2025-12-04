<?php

namespace EasyPack\Http\Controllers\Api\V1;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\User;
use EasyPack\ApiDocs\Docs\APICall;
use EasyPack\ApiDocs\Docs\Param;
use EasyPack\ApiDocs\Docs\ParamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Check and process password reset request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkRequest(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Forgot Password')
                ->setDescription('Send password reset link to email')
                ->setParams([
                    (new Param('email', ParamType::STRING, 'Email address'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->apiError('We could not find a user with that email address.', 404);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->apiSuccess(null, 'Password reset link sent to your email.');
        }

        return response()->apiError('Unable to send password reset link.', 500);
    }
}
