<?php

namespace EasyPack\Models;

use Illuminate\Database\Eloquent\Model;

class PageContent extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'page_contents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'title',
        'content',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get page content by slug.
     *
     * @param string $slug
     * @return static|null
     */
    public static function getBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get formatted content (can be extended for additional processing).
     *
     * @return string
     */
    public function getFormattedContentAttribute(): string
    {
        return $this->content;
    }

    /**
     * Get default content by slug.
     *
     * @param string $slug
     * @return string
     */
    public static function getDefaultContent(string $slug): string
    {
        return match ($slug) {
            'privacy-policy' => self::privacyPolicyTemplate(),
            'terms-conditions' => self::termsConditionsTemplate(),
            default => '',
        };
    }

    /**
     * Get default privacy policy content.
     */
    protected static function privacyPolicyTemplate(): string
    {
        return <<<'HTML'
<h3>What information do we collect?</h3>
<p>We collect information from you when you register on our site, place an order, subscribe to our newsletter, respond to a survey or fill out a form.</p>

<p>When ordering or registering on our site, as appropriate, you may be asked to enter your: name, e-mail address, mailing address or phone number. You may, however, visit our site anonymously.</p>

<h3>What do we use your information for?</h3>
<p>Any of the information we collect from you may be used in one of the following ways:</p>

<ul>
    <li>
        To personalize your experience
        <blockquote><p>(your information helps us to better respond to your individual needs)</p></blockquote>
    </li>

    <li>
        To improve our website
        <blockquote><p>(we continually strive to improve our website offerings based on the information and feedback we receive from you)</p></blockquote>
    </li>

    <li>
        To improve customer service
        <blockquote><p>(your information helps us to better respond to your individual needs)</p></blockquote>
    </li>

    <li>
        To process transactions
        <blockquote><p>Your information, whether public or private, will not be sold, exchanged, transferred, or given to any other company for any reason whatsoever, without your consent, other than for the express purpose of delivering the purchased product or service requested.</p></blockquote>
    </li>

    <li>
        To administer a contest, promotion, survey or other site feature
        <blockquote><p>Your information, whether public or private, will not be sold, exchanged, transferred, or given to any other company for any reason whatsoever, without your consent, other than for the express purpose of delivering the purchased product or service requested.</p></blockquote>
    </li>

    <li>
        To send periodic emails
        <blockquote><p><p>The email address you provide for order processing, may be used to send you information and updates pertaining to your order, in addition to receiving occasional company news, updates, related product or service information, etc.</p></blockquote>
    </li>

    <p>Note: If at any time you would like to unsubscribe from receiving future emails, we include detailed unsubscribe instructions at the bottom of each email. Alternatively, you can contact us via email or phone and request your contacts to be unsubscribed from any emails.</p>

</ul>

<h3>How do we protect your information?</h3>
<p>We implement a variety of security measures to maintain the safety of your personal information when you place an order or enter, submit, or access your personal information.</p>

<h3>Do we use cookies?</h3>
<p>Yes (Cookies are small files that a site or its service provider transfers to your computers hard drive through your Web browser (if you allow) that enables the sites or service providers systems to recognize your browser and capture and remember certain information</p>

<h3>Google and Other Advertising Networks</h3>
<p>
    We use Google AdWords and other online advertising networks for online advertising and remarketing services across the Internet, including on the Google Display Network.<br>

    AdWords remarketing will display ads to you based on what parts of this website or mobile app you have viewed by placing a cookie on your web browser or device.<br>

    This cookie does not in any way identify you or give access to your computer or mobile device.<br>

    The cookie is used to indicate to other websites that  "This person visited a particular page, so show them ads relating to that page."<br>

    These advertising networks allows us to tailor our marketing to better suit your needs and only display ads that are relevant to you.<br>

    If you do not wish to see ads related to this website or mobile app, you can opt out in several ways: <br>
    1. Opt out of Google's use of cookies by visiting Google's Ads Settings. <br>
    2. Opt out of a third-party vendor's use of cookies by visiting the Network Advertising Initiative opt-out page.
    <br>
</p>

<p>We use cookies to help us remember and process the items in your shopping cart, understand and save your preferences for future visits, keep track of advertisements and compile aggregate data about site traffic and site interaction so that we can offer better site experiences and tools in the future. We may contract with third-party service providers to assist us in better understanding our site visitors. These service providers are not permitted to use the information collected on our behalf except to help us conduct and improve our business.</p>

<h3>Do we disclose any information to outside parties?</h3>
<p>We do not sell, trade, or otherwise transfer to outside parties your personally identifiable information. This does not include trusted third parties who assist us in operating our website, conducting our business, or servicing you, so long as those parties agree to keep this information confidential. We may also release your information when we believe release is appropriate to comply with the law, enforce our site policies, or protect ours or others rights, property, or safety. However, non-personally identifiable visitor information may be provided to other parties for marketing, advertising, or other uses.</p>

<h3>Third party links</h3>
<p>Occasionally, at our discretion, we may include or offer third party products or services on our website. These third party sites have separate and independent privacy policies. We therefore have no responsibility or liability for the content and activities of these linked sites. Nonetheless, we seek to protect the integrity of our site and welcome any feedback about these sites.</p>

<h3>Online Privacy Policy Only</h3>
<p>This online privacy policy applies only to information collected through our website and not to information collected offline.</p>

<h3>Your Consent</h3>
<p>By using our site, a mobile app or a software which uses an API to access the data, you consent to our privacy policy.</p>

<h3>Changes to our Privacy Policy</h3>
<p>If we decide to change our privacy policy, we will post those changes on this page.</p>

<h3>Contacting Us</h3>
<p>If there are any questions regarding this privacy policy you may contact us using the contact information on the contact section (This is available form the site's navigation).</p>
HTML;
    }

    /**
     * Get default terms & conditions content.
     */
    protected static function termsConditionsTemplate(): string
    {
        return <<<'HTML'
<h2>Terms and Conditions of Use</h2>

<h3>1. Terms</h3>

<p>
    By accessing this web site, you are agreeing to be bound by these
    web site Terms and Conditions of Use, all applicable laws and regulations,
    and agree that you are responsible for compliance with any applicable local
    laws. If you do not agree with any of these terms, you are prohibited from
    using or accessing this site. The materials contained in this web site are
    protected by applicable copyright and trade mark law.
</p>

<h3>2. Use License</h3>

<ol type="a">
    <li>
        Permission is granted to temporarily download one copy of the materials
        (information or software) on this web site for personal,
        non-commercial transitory viewing only. This is the grant of a license,
        not a transfer of title, and under this license you may not:

        <ol type="i">
            <li>modify or copy the materials;</li>
            <li>use the materials for any commercial purpose, or for any public display (commercial or non-commercial);</li>
            <li>attempt to decompile or reverse engineer any software contained on this web site;</li>
            <li>remove any copyright or other proprietary notations from the materials; or</li>
            <li>transfer the materials to another person or "mirror" the materials on any other server.</li>
        </ol>
    </li>
    <li>
        This license shall automatically terminate if you violate any of these restrictions and may be terminated at any time. Upon terminating your viewing of these materials or upon the termination of this license, you must destroy any downloaded materials in your possession whether in electronic or printed format.
    </li>
</ol>

<h3>3. Disclaimer</h3>

<ol type="a">
    <li>
        The materials on this web site are provided "as is". We make no warranties, expressed or implied, and hereby disclaims and negates all other warranties, including without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights. Further, we do not warrant or make any representations concerning the accuracy, likely results, or reliability of the use of the materials on its Internet web site or otherwise relating to such materials or on any sites linked to this site.
    </li>
</ol>

<h3>4. Limitations</h3>

<p>
    In no event shall we or our suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption,) arising out of the use or inability to use the materials on our Internet site, even if we or an authorized representative has been notified orally or in writing of the possibility of such damage. Because some jurisdictions do not allow limitations on implied warranties, or limitations of liability for consequential or incidental damages, these limitations may not apply to you.
</p>

<h3>5. Revisions and Errata</h3>

<p>
    The materials appearing on our web site could include technical, typographical, or photographic errors. We do not warrant that any of the materials on its web site are accurate, complete, or current. We may make changes to the materials contained on its web site at any time without notice. We do not, however, make any commitment to update the materials.
</p>

<h3>6. Links</h3>

<p>
    We have not reviewed all of the sites linked to its Internet web site and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by us of the site. Use of any such linked web site is at the user's own risk.
</p>

<h3>7. Site Terms of Use Modifications</h3>

<p>
    We may revise these terms of use for its web site at any time without notice. By using this web site you are agreeing to be bound by the then current version of these Terms and Conditions of Use.
</p>

<h3>8. Governing Law</h3>

<p>
    Any claim relating to our web site shall be governed by the laws of the State of Victoria without regard to its conflict of law provisions.
</p>

<p>
    General Terms and Conditions applicable to Use of a Web Site.
</p>
HTML;
    }
}
