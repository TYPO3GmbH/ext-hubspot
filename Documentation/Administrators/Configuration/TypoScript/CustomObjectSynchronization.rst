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

.. _configuration-customobjects-objectName:

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


