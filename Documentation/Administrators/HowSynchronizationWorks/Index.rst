.. include:: /Includes.txt

.. _how-sync-works:

=================================
How Hubspot Synchronization Works
=================================

Synchronization of contacts and custom objects work much in the same way, but
with some implementational differences due to the character of the data and
data structure.

.. _how-sync-works-contacts:

How Hubspot contact synchronization works
=========================================

The synchronization process is split into three steps.

.. _1-check-hubspot-for-new-contacts:

1. Check Hubspot for new contacts
---------------------------------

If :ref:`enabled<configuration-typoscript-synchronize-createNewInTypo3>`, the
script checks for the presence of new Hubspot contacts.

The Hubspot contact's creation timestamp is stored in the field
`hubspot_created_timestamp` (a BIGINT property) in the `fe_users` table, so that
TYPO3 always knows which Hubspot contact is the newest. This makes it possible
to find Hubspot contacts created since this date.

If no Hubspot creation timestamps have been set, the script will look for new
Hubspot contacts before the current timestamp (effectively all Hubspot
contacts).

If the email address of the Hubspot contact exists in the `email` field of the
`fe_users` table (within any page ID limitations set), this record will be
assigned as the Hubspot contact's equivalent Frontend User. This prevents email
address duplication.

If no matching Frontend User record is found, a new Frontend User record is
created.

The Hubspot contact's ID is stored in the `hubspot_id` field of the `fe_users`
table.

If this step is executed and more than zero new Hubspot contacts were found,
the script will end after this step. This will continue each time the script is
executed, until there are no more new Hubspot contacts.

.. warning::
   Please note that new Frontend Users are not automatically assigned a
   password. You will have to
   :ref:`configure<configuration-typoscript-synchronize-toFrontendUser-property>`
   this yourself.

.. _2-find-frontend-users-not-yet-synced:

2. Find Frontend Users not yet synced
-------------------------------------

If :ref:`enabled<configuration-typoscript-synchronize-createNewInHubspot>`, the
script will look for Frontend User records without a `hubspot_id` property set.

If the email address of the Frontend User exists in a Hubspot contact this
contact will be assigned as the Frontend User's hubspot contact equivalent. This
prevents email address duplication.

Otherwise, a new Hubspot contact will be created.

The Hubspot contact's ID is stored in the `hubspot_id` field of the `fe_users`
table.

If this step is executed and more than zero Frontend User contacts were found,
the script will end after this step. This will continue each time the script is
executed, until there are no more Frontend Users that have not been synced.

.. _3-synchronize-typo3-and-hubspot-contact-data:

3. Synchronize linked Frontend Users and Hubspot contacts
---------------------------------------------------------

The script defines one Sync Pass as the synchronization of all user records. As
this may be achieved only by running the script multiple times, the
`hubspot_sync_pass` property of the `fe_users` table is used to keep track of
which Frontend Users have been synced during the current Sync Pass.

The script selects Frontend Users ready for synchronization. The Frontend User's
`tstamp` property is used to see when the record was last changed by TYPO3.

Hubspot maintains modfification timestamps for *each* property, and the script
compares these timestamps with the Frontend User's `tstamp` to check whether or
not a property has been updated. This comparison results in a list of fields
that should be synchronized one way or another.

Property values are rendered for both Frontend Users and Hubspot contacts. These
properties are compared and only changed properties that are not the same
between TYPO3 and Hubspot are persisted to TYPO3 and Hubspot.

.. _how-sync-works-custom-objects:

How Hubspot contact synchronization works
=========================================

The synchronization process is split into three steps.

Relation table
--------------

Information about Hubspot custom object relations to TYPO3 records are kept in a
separate table called `tx_hubspot_mapping`. Data is fetched from the TYPO3
records using the special repository class
:php:`T3G\Hubspot\Domain\Repository\Database\MappedTableRepository`, which
performs a join operation between the mapping table and the record's table. The
repository adds all necessary restrictions to keep record tables apart.

The field `tx_hubspot_mapping.hubspot_id` keeps the ID of the record in Hubspot.
This ID may be set to zero in cases where a record is skipped by throwing one of
these exceptions (usually from within an event handler):

* :php:`T3G\Hubspot\Service\Exception\SkipRecordSynchronizationException`
* :php:`T3G\Hubspot\Service\Exception\StopRecordSynchronizationException`

.. _1-check-hubspot-for-new-contacts:

1. Check Hubspot for new custom objects
---------------------------------------

.. info::
   This step is currently not implemented. The new Hubspot API doesn't have an
   endpoint corresponding to the old API's *Get recently updated and created*
   *contacts* endpoint.

.. _2-find-custom-objects-not-yet-synced:

2. Find custom objects not yet synced
-------------------------------------

If :ref:`enabled<configuration-customobjects-createNewInHubspot>`, the
script will look for custom object records without a `hubspot_id` property set.

A list of unique properties are fetched from the custom object schema
(`hasUniqueValue = 1`). The values of these fields are always unique between
custom objects, so we can use them for lookup operations.

If a custom object with a unique property exists, it will be assigned as the
TYPO3 record's Hubspot equivalent. This prevents duplication and errors.

.. warning::
   Including at least one unique property in your custom object schema is
   required if you would like to connect existing records in Hubspot and TYPO3.

If no Hubspot equivalent is found, a new Hubspot custom object will be created.

The Hubspot contact's ID is stored in the `hubspot_id` field of the
`tx_hubspot_mapping` table.

.. _3-synchronize-typo3-and-hubspot-custom-object-data:

3. Synchronize linked TYPO3 records and Hubspot custom objects
--------------------------------------------------------------

The script defines one Sync Pass as the synchronization of all custom objects
synchronization key. This may be achieved only by running the script multiple
times, the `hubspot_sync_pass` property of the `tx_hubspot_mapping` table is
used to keep track of which TYPO3 records and Hubspot custom objects have been
synced during the current Sync Pass.

The script selects TYPO3 records ready for synchronization. The record's
`tstamp` property is used to see when the record was last changed by TYPO3.

Hubspot maintains modfification timestamps for *each* property, and the script
compares these timestamps with the TYPO3 record's `tstamp` to check whether or
not a property has been updated. This comparison results in a list of fields
that should be synchronized one way or another.

Property values are mapped for both TYPO3 records and Hubspot custom objects.
These properties are compared and only changed properties that are not equal
between TYPO3 and Hubspot are persisted to TYPO3 and Hubspot.
