.. include:: /Includes.txt

.. _install-and-setup:

======================
Installation and Setup
======================

.. _install-and-setup-download:

Downloading the latest version
==============================

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

.. _install-and-setup-latest-git:

Latest version from Git
-----------------------
You can get the latest version from git by using the git command:

.. code-block:: bash

   git clone git@github.com:TYPO3GmbH/ext-hubspot.git

.. _install-and-setup-authentication:

Authentication
==============

Hubspot authentication is done via ENV vars (APP_HUBSPOT_PORTALID and
APP_HUBSPOT_TOKEN). You need to know your Hubspot Portal-ID and create a private
app to get an OAuth2 access token.

.. _install-and-setup-get-api-key:
.. _install-and-setup-get-token:

How to get your access token
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In order to get an OAuth2 access token you have to create a private app in your
Hubspot instance. Read `how to create a private app <https://developers.hubspot.com/docs/api/private-apps#create-a-private-app>`__
in the Hubspot Documentation.

.. _install-and-setup-find-portal-id:

How to find your Portal ID
--------------------------

You can find your Hubspot Portal ID in the upper right hand corner, after
logging into your account. It is labeled "Hub ID".

.. _install-and-setup-set-up-secrets:

How to set up your authentication secrets
=========================================

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

.. _install-and-setup-typoscript:

Add TypoScript and the static template
======================================

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

.. _install-and-setup-custom-object-schemas:

Setting up Custom Object schemas
================================

If your are going to synchronize custom objects, you must set up at least one
Custom Object schema. All custom objects you would like to synchronize must have
a schema up-to-date and configured in TYPO3.

.. rst-class:: bignums-xxl

1. Go to the backend module

   To start, go to the TYPO3 backend and find the module
   :guilabel:`Admin Tools > Hubspot Integration`, then click on
   :guilabel:`Custom Objects`

   .. figure:: Images/BackendModuleOverview.png
      :alt: The main overview section of the Hubspot TYPO3 Backend module

      The main overview section of the Hubspot TYPO3 Backend module contains
      three items.

   If this is the first time you are using the custom object synchronization,
   the list of custom object schemas is most likely empty.

   .. figure:: Images/EmptyCustomObjectSchemaList.png
      :alt: An empty list with the title Custom Object Schemas

      TYPO3 is aware of no custom object schemas.

2. Click the :guilabel:`Fetch Updates` button in the docheader

   This will download existing custom object schemas from Hubspot and add them
   to the list. TYPO3 is aware of all the custom object schemas you can see in
   the list.

   If there are no custom objects defined in Hubspot, you'll still see an empty
   list.

   If all the custom objects you would like to synchronize are now in the list,
   you can choose to stop here.

3. Declare the JSON schema files you are planning to use

   Read more about :ref:`declaring JSON files<backend-custom-objects-declare>`.

4. Click the :guilabel:`New` button in the docheader

   You will see a list of all the custom object schema JSON files that have been
   declared.

   .. figure:: Images/ListOfDeclaredJsonSchemaFiles.png
      :alt: A list with hubspot custom object schema file.

      Custom object schemas appear in the list when they have been declared.

5. Click the :guilabel:`Create From File` button

   Click on the button next to the file containing the custom object schema you
   would like to add to Hubspot.

   If the creation is successful, the new schema will appear in the Custom
   Object Schema list.

   .. tip::

      You can repeat this step again later to update the schema in Hubspot. This
      makes it possible for you to update the file and then push the changes to
      the connected Hubspot account.
