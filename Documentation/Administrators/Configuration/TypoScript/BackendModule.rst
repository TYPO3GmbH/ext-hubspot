.. include:: /Includes.txt

.. _configuration-backendmodule:

==============
Backend Module
==============

.. _configuration-backendmodule-downloadfilterschema:

Download a schema as a JSON file
================================

TypoScript variables within `module.tx_hubspot.download.filterSchema`.

.. _configuration-backendmodule-downloadfilterschema-enabled:

enabled
-------

:aspect:`Property`
   enabled

:aspect:`Data type`
   Boolean

:aspect:`Default`
   false

:aspect:`Description`
   If true, fetched schema will be filtered before download

.. _configuration-backendmodule-downloadfilterschema-allowedMainProperties:

allowedMainProperties
---------------------

:aspect:`Property`
   allowedMainProperties

:aspect:`Data type`
   Commaseparated list of allowed main properties

:aspect:`Default`
   name, labels, primaryDisplayProperty, secondaryDisplayProperties, searchableProperties, requiredProperties, properties, associatedObjects, metaType

:aspect:`Description`
   List of allowed main properties. All main properties not incuded in list
   will be filtered out.

.. _configuration-backendmodule-downloadfilterschema-allowedMainProperties:

allowedPropertyProperties
-------------------------

:aspect:`Property`
   allowedPropertyProperties

:aspect:`Data type`
   Commaseparated list of allowed property properties

:aspect:`Default`
   name, label, type, fieldType, groupName, options

:aspect:`Description`
   List of allowed property properties. All properties not incuded in list
   will be filtered out.

.. _configuration-backendmodule-downloadfilterschema-excludeHubspotDefinedProperties:

excludeHubspotDefinedProperties
-------------------------------

:aspect:`Property`
   excludeHubspotDefinedProperties

:aspect:`Data type`
   Boolean

:aspect:`Default`
   true

:aspect:`Description`
   If true, HubSpot defined properties will be excluded from
   schema before download
