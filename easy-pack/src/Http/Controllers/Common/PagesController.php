<?php

namespace EasyPack\Http\Controllers\Common;

use EasyPack\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PagesController extends Controller
{
    /**
     * Show Privacy Policy Page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function privacyPolicy()
    {
        return view('easypack::pages.privacy', ['pageTitle' => 'Privacy Policy']);
    }

    /**
     * Show Terms & Conditions Page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function termsConditions()
    {
        return view('easypack::pages.terms', ['pageTitle' => 'Terms & Conditions']);
    }

    /**
     * Show Contact Us Page
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function contactUs()
    {
        return view('easypack::pages.contact-us', ['pageTitle' => 'Contact Us']);
    }

    /**
     * Submit Contact Us Page
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postContactUs(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'userMessage' => 'required|max:255'
        ]);
        
        $data = $request->only('name', 'email', 'phone', 'userMessage');

        // recaptcha validation (optional)
        if (config('easypack.features.recaptcha_enabled', false)) {
            // Note: This requires google/recaptcha package
            // composer require google/recaptcha
            $secret = env('RECAPTCHA_SECRET_KEY');
            if ($secret) {
                try {
                    $recaptcha = new \ReCaptcha\ReCaptcha($secret);
                    $response = $recaptcha->verify($request->input('g-recaptcha-response'), $request->ip());
                    if (!$response->isSuccess()) {
                        return redirect()->route('contact-us')
                            ->with('error', 'Captcha validation failed. Try again or email us for support.')
                            ->withInput($data);
                    }
                } catch (\Exception $e) {
                    // If ReCaptcha class doesn't exist, continue without validation
                    logger()->warning('ReCaptcha validation skipped: ' . $e->getMessage());
                }
            }
        }

        $data['timestamp'] = Carbon::now()->format('d/m/Y h:i:sA');
        $data['userIp'] = request()->ip();
        $data['sender_email'] = $request->get('email');

        $webmaster = config('easypack.webmaster_email') ?? env('WEBMASTER_EMAIL');
        if (empty($webmaster)) {
            return redirect()->back()
                ->with('error', 'Email configuration is missing. Please contact the administrator.')
                ->withInput($data);
        }
        $receiverEmails = [$webmaster];

        Mail::send(['text' => 'easypack::emails.text.contact-us-email'], $data, function ($mailMessage) use ($data, $receiverEmails) {
            $mailMessage->to($receiverEmails)
                ->replyTo($data['sender_email'])
                ->subject(config('app.name') . ' - Contact Us - Message Received');
        });

        return redirect()->back()->with('success', 'Your message has been sent.');
    }
}
