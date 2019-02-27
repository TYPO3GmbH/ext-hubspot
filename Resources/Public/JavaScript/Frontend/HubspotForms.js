/**
 * Module: TYPO3/CMS/Hubspot/Frontend/HubspotForms
 */
define(['//js.hsforms.net/forms/v2.js'], function () {
    window.addEventListener('load', function () {
        const hubspotFormContainers = document.querySelectorAll('.t3js-hubspot-form');
        Array.from(hubspotFormContainers).forEach(function (container) {
            const disableCss = parseInt(container.dataset.disableCss || 1, 10) === 1;
            const portalId = container.dataset.portalId;
            const formId = container.dataset.formId;

            const options = {
                portalId: portalId,
                formId: formId,
                target: '#' + container.id,
                onFormReady: function () {
                    const messageElement = document.querySelector("#hubspot-form-message");
                    messageElement.parentElement.removeChild(messageElement);
                }
            };

            if (disableCss) {
                options.css = '';
            }

            hbspt.forms.create(options);
        });
    });
});
