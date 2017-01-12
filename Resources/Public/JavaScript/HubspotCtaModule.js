/**
 * Module: TYPO3/CMS/Hubspot/HubspotCtaModule
 */
define(['jquery'], function ($) {
    'use strict';

    var HubspotCta = {
        initialize: function () {
            $(document).on("focus", "textarea[name*='hubspot_cta_code']", function () {
                $(this).select();
            });
        }
    };

    $(HubspotCta.initialize);
});
