<?php

namespace EasyPack\Http\Controllers\Api\V1;

use EasyPack\Http\Controllers\Controller;
use EasyPack\ApiDocs\Docs\APICall;
use Illuminate\Http\JsonResponse;

class GuestController extends Controller
{
    /**
     * Get guest/public settings.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setName('Guest Settings')
                ->setGroup('Guest')
                ->setDescription('Get guest/public settings and URLs')
                ->setSuccessMessageOnly();
        });

        $data = [
            'privacy_policy_url' => config('app.privacy_policy_url'),
            'terms_conditions_url' => config('app.terms_conditions_url'),
            'about_us_url' => config('app.about_us_url'),
            'website_url' => config('app.url'),
            'app_name' => config('app.name'),
        ];

        return response()->apiSuccess($data, 'Success');
    }
}
