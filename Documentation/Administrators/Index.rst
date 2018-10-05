Administration
==============


Installation
------------

The extension needs to be installed as any other extension of TYPO3 CMS:

#. Switch to the module “Extension Manager”.

#. Get the extension

   #. **Get it from the Extension Manager:** Press the “Retrieve/Update”
      button and search for the extension key *hubspot* and import the
      extension from the repository.

   #. **Get it from typo3.org:** You can always get a current version from
      `https://typo3.org/extensions/repository/view/hubspot/current/
      <https://typo3.org/extensions/repository/view/hubspot/current/>`_ by
      downloading either the t3x or zip version. Upload
      the file afterwards in the Extension Manager.

   #. **Use composer**: Use `composer require T3G/hubspot`.

Latest version from git
-----------------------
You can get the latest version from git by using the git command:

.. code-block:: bash

   git clone ssh://git@bitbucket.typo3.com:7999/ext/hubspot.git


Setup
-----

Authentication
^^^^^^^^^^^^^^

Hubspot authentication is done via ENV vars (APP_HUBSPOT_PORTALID and
APP_HUBSPOT_SECRET). You need to know your Hubspot Portal-ID and API key.

How to get your API key
^^^^^^^^^^^^^^^^^^^^^^^

By clicking on your username in the upper right hand corner, you will find
the menu item "Integrations". Here you can generate a new API key. Hubspot
allows you to generate only one API key at a time. This means that each and
every integration and application share this one key. The key belongs to a
specific installation and not to an individual user.

How to find your Portal ID
^^^^^^^^^^^^^^^^^^^^^^^^^^

You can find your Hubspot Portal ID in the upper right hand corner, after
logging into your account. It is labelled "Hub ID".

How to set up your authentication secrets
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The authentication secrets are set via environment variables. You can
easily do this inside your .htaccess file:


.. code-block:: bash

   # Add your own rules here.
   SetEnv TYPO3_CONTEXT Development
   SetEnv APP_HUBSPOT_PORTALID 1234567
   SetEnv APP_HUBSPOT_SECRET 12345678-1234-1234-1234-123456789012

When working in composer mode there is the popular package "dotenv-connector"
from Helmut Hummel, which makes it possible to set environment variables for
composer projects:

.. code-block:: bash

   composer require helhum/dotenv-connector

`https://packagist.org/packages/helhum/dotenv-connector
      <https://packagist.org/packages/helhum/dotenv-connector>`_

Add TypoScript and the static template
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Before using the Hubspot extension you need to include the static extension
template. Go to the Template module and open your main TypoScript template.
Click on "Edit the whole template record" and switch to the "Includes" tab.
Go to the “Include static (from extensions)” section and add the template
"Hubspot Integration (hubspot)" from the list of available items.

To include the Page TSConfig you need to switch to the Page module, select
your top level page and edit the page properties. You can also find the
available template "Hubspot Integration (hubspot)" in the available list of
items. You can add it to the "Include Page TSConfig (from extensions)" list
by simply clicking it.

You are now ready to use the hubspot extension!

Backend Module
--------------

The hubspot extensions provides a backend module for administrators. You can find
it in the module menu at `Admin Tools > Hubspot Integration`.
The backend module contains two sub-modules: Forms and CTAs. Both work the same way:
They display which pages and content elements (non-deleted) currently contain
hubspot elements. You are able to switch to the content element itself or directly edit
the form in Hubspot. In order for it to work, you have to be logged into
Hubspot.
