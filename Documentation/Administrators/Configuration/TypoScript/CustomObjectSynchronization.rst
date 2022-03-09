.. include:: /Includes.txt

.. _configuration-customobjects:

=============================
Custom Object Synchronization
=============================

.. _configuration-customobjects-persistence:

General Persistence
===================

TypoScript variables within `module.tx_hubspot.persistence`.

.. _configuration-customobjects-storagepid:

synchronizeCustomObjects.storagePid
-----------------------------------

:aspect:`Property`
   synchronize.storagePid

:aspect:`Data type`
   Integer

:aspect:`Description`
   Page ID for storage of Frontend User records created during synchronization.

.. _configuration-customobjects-settings:

Custom Object Synchronization Settings
======================================

You can define any number of custom object synchronizations in
`module.tx_hubspot.settings.synchronizeCustomObjects.[key]`.

`key` can be any key, but the Hubspot object name is recommended. Still, there
could be multiple syncs to the same object, so it isn't a requirement.

.. _configuration-customobjects-wildcard:

The wildcard key
----------------

The key `module.tx_hubspot.settings.synchronizeCustomObjects.*` can contain
default values that are merged and overridden by specific values from a key.
This wildcard key is used by the CLI command, e.g. when setting the `limit`
property.

.. _configuration-customobjects-associations:

associations.[foreignObjectName]
--------------------------------

:aspect:`Property`
   associations.[foreignObjectName]

:aspect:`Data type`
   String | stdWrap

:aspect:`Description`
   Specifies a relation between this custom object and another Hubspot object.
   (In Hubspot relations are called *associations*.) `[foreignObjectName]` is
   the name of the foreign object. In combination with the name of your custom
   object it will generate an association name with the format
   `[objectName]_to_[foreignObjectName]`, e.g. `shipment_to_contact`. The value
   must be a record ID (UID) of the corresponding record in TYPO3. The
   foreign object must be configured as a custom object or contact using this
   extension, so it's possible to look up the Hubspot ID based on the record ID.

Example
~~~~~~~

.. code-block:: typoscript

   associations {
     # The "feuser" field in the TYPO3 record contains an ID we can use to
     # find a corresponding Hubspot ID and set up the association.
     contact = feuser
   }

.. _configuration-customobjects-createNewInHubspot:

createNewInHubspot
------------------

:aspect:`Property`
   createNewInHubspot

:aspect:`Data type`
   Boolean

:aspect:`Description`
   If true, the synchronization operation will create a new hubspot custom
   object whenever it comes across a record that doesn't exist in Hubspot.

.. _configuration-customobjects-createNewInTypo3:

createNewInTypo3
----------------

:aspect:`Property`
   createNewInTypo3

:aspect:`Data type`
   Boolean

:aspect:`Description`
   If true, the synchronization operation will create a new TYPO3 record
   whenever it comes across a Hubspot custom object that doesn't exist in TYPO3.

.. _configuration-customobjects-ignoreOnHubspotCreate:

ignoreOnHubspotCreate
---------------------

:aspect:`Property`
   ignoreOnHubspotCreate

:aspect:`Data type`
   Comma-separated list

:aspect:`Description`
   Hubspot properties that should not be included when creating a new custom
   object.

.. _configuration-customobjects-ignoreOnHubspotUpdate:

ignoreOnHubspotUpdate
---------------------

:aspect:`Property`
   ignoreOnHubspotUpdate

:aspect:`Data type`
   Comma-separated list

:aspect:`Description`
   Hubspot properties that should not be included when updating a new custom
   object.

.. _configuration-customobjects-ignoreOnLocalCreate:

ignoreOnLocalCreate
---------------------

:aspect:`Property`
   ignoreOnLocalCreate

:aspect:`Data type`
   Comma-separated list

:aspect:`Description`
   Local field values that should not be included when creating a new TYPO3
   record.

.. _configuration-customobjects-ignoreOnLocalUpdate:

ignoreOnLocalUpdate
---------------------

:aspect:`Property`
   ignoreOnLocalUpdate

:aspect:`Data type`
   Comma-separated list

:aspect:`Description`
   Local field values that should not be included when updating a new TYPO3
   record.

.. _configuration-customobjects-limitToPids:

limitToPids
-----------

:aspect:`Property`
   limitToPids

:aspect:`Data type`
   Comma-separated list

:aspect:`Description`
   Limit record queries in TYPO3 to page IDs in this list.

.. _configuration-customobjects-objectname:

objectName
----------

:aspect:`Property`
   storagePid

:aspect:`Data type`
   String

:aspect:`Description`
   The object name in Hubspot. This is the `name` property in the custom object
   schema, e.g. "shipment".

.. warning::
   Do not mix it up with the `fullyQualifiedName` in the custom object schema,
   e.g. "p12345678_shipment". We are using the simple object name only for
   portability reasons, so you can use the same configuration for both
   development and production.

.. _configuration-customobjects-table:

table
-----

:aspect:`Property`
   table

:aspect:`Data type`
   String

:aspect:`Description`
   The name of the database table to use for the TYPO3-side of the
   synchronization.

.. _configuration-customobjects-toHubspot:

toHubspot.[hubspotPropertyName]
-------------------------------

:aspect:`Property`
   toHubspot.[hubspotPropertyName]

:aspect:`Data type`
   String | stdWrap

:aspect:`Description`
   Maps a TYPO3 record field to a Hubspot property. This is used when syncing
   from TYPO3 to Hubspot.

Example
~~~~~~~

.. code-block:: typoscript

   toHubspot {
     # The value of the "title" field in TYPO3 is used for the "name" property
     # in Hubspot.
     name = title

     # You can transform the value using stdWrap.
     description = content
     description.ifEmpty = NO DESCRIPTION

     # Date fields must be formatted correctly for Hubspot.
     date = crdate
     date.date = Y-m-d
   }

.. _configuration-customobjects-toLocal:

toLocal.[typo3fieldName]
------------------------

:aspect:`Property`
   toLocal.[typo3fieldName]

:aspect:`Data type`
   String | stdWrap

:aspect:`Description`
   Maps a Hubspot property to a TYPO3 field. This is used when syncing from
   Hubspot to TYPO3.

Example
~~~~~~~

.. code-block:: typoscript

   toLocal {
     # The value of the "name" property in Hubspot is used for the "title" field
     # in TYPO3.
     title = name

     # You can transform the value using stdWrap.
     bodytext = description
     bodytext.ifEmpty = NO CONTENT

     # Date fields must be formatted correctly for TYPO3's Unix timestamps.
     crdate = date
     crdate.date = u
   }

Examples
========

.. code-block:: typoscript

   module.tx_hubspot {
     persistence {
       synchronizeCustomObjects.storagePid = 234
     }

     settings {
       synchronizeCustomObjects {
         product_registration {
           objectName = shipping

           limitToPids < module.tx_hubspot.persistence.synchronizeCustomObjects.storagePid

           table = tx_pxasightregister_domain_model_sight

           createNewInHubspot = 1
           createNewInTypo3 = 0

           toHubspot {
             name = model
             name {
               cObject = CONTENT
               cObject {
                 table = tx_myextension_product
                 select {
                   uidInList.field = model
                 }

                 renderObj = TEXT
                 renderObj {
                   value = {field:name} ({field:product_number})
                   insertData = 1
                 }
               }

               ifEmpty = [UNKNOWN]
             }

             introduced = introduction_date
             introduced.date = Y-m-d

             sales_price = price

             sku = product_number
           }

           associations {
             contact = feuser
           }

           ignoreOnHubspotCreate =
           ignoreOnHubspotUpdate = sku

           toLocal {
             price = sales_price
             product_number = sku
           }

           ignoreOnLocalCreate =
           ignoreOnLocalUpdate =
         }
       }
     }
   }
