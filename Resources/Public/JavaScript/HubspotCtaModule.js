/**
 * Module: TYPO3/CMS/Hubspot/HubspotCtaModule
 */
define(['jquery'], function ($) {
    'use strict';

    var HubspotCta = {
        initialize: function () {
            $(":input[name*='hubspot_cta']").on("click", function () {
                $(this).select();
            });
        }
    };

    $(HubspotCta.initialize);
});
