# Hubspot Integration for TYPO3

## Authentication

Hubspot authentication is done via ENV var "HUBSPOT_SECRET" - add your hapi key there.

## Configuration

- Add the TypoScript template at "Include static"
- Include the Page TSConfig
- Set the ENV vars HUBSPOT_SECRET (see Authentication) and HUBSPOT_PORTALID (to your Hubspot hapi key and portal ID)

## Forms

Forms can be inserted as content elements and will be rendered via the Hubspot Forms JS.

## The backend module

The Hubspot integration backend module displays an overview of all used hubspot elements and
where you can find them. For example if you need to know which forms are used where, use the forms
overview.
