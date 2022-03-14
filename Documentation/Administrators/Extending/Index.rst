.. include:: /Includes.txt

.. _extending:

=========================
Extending Synchronization
=========================

.. _extending-typoscript:

Using TypoScript
================

The mapping configuration supports :typoscript:`stdWrap`. This gives a number of
possibilities for enriching and transforming the data sent to Hubspot.

Take a look at the documentation for :ref:`contact<configuration-contactsync>`
and :ref:`custom object<configuration-customobjects>` synchronization for an
overview of the supported configuration options.

.. _extending-events:

Using Events and Signal Slots
=============================

You can hook your own code into the synchronization process using the large
number of PSR-14 events and signals inside the synchronization classes.

.. _extending-events-skip-or-stop:

Exceptions to stop or skip synchronization
------------------------------------------

Event handlers and signal slots can throw exceptions to skip or stop the
synchronization:

`\T3G\Hubspot\Service\Exception\SkipRecordSynchronizationException`
   Will skip further processing of the current frontend user or custom object
   record.
`\T3G\Hubspot\Service\Exception\StopRecordSynchronizationException`
   Will stop all further processing of frontend users or custom objects this
   synchronization run.

.. _extending-events-eventhandler:

Creating an EventHandler/SignalSlot class
-----------------------------------------

An EventHandler and SignalSlot class implementation is identical as long as it
is registered using the correct APIs in the correct TYPO3 version.

All events include matching interfaces that make it easy to implement the
handler/slot classes.

The following example will prevent a frontend user from being added to Hubspot
if the user's country is "USA".

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Foo\Bar\EventListener\Hubspot;

   use T3G\Hubspot\Service\Event\BeforeAddingFrontendUserToHubspotEvent;
   use T3G\Hubspot\Service\Event\BeforeAddingFrontendUserToHubspotEventHandlerInterface;
   use T3G\Hubspot\Service\Exception\SkipRecordSynchronizationException;

   /**
    * Prevents Hubspot sync from adding contacts from USA.
    */
   class SkipFrontendUserSyncIfCountryIsUsaEventHandler implements BeforeAddingFrontendUserToHubspotEventHandlerInterface
   {
       /**
        * @inheritDoc
        */
       public function __invoke(BeforeAddingFrontendUserToHubspotEvent $event): void
       {
           if (strtoupper($event->getFrontendUser()['country']) === 'USA') {
               throw new SkipRecordSynchronizationException();
           }
       }
   }

This is a real-life example from a customer that has separate Hubspot accounts
for the United States of America and the rest of the world.

.. _extending-events-register:

Registering the EventHandler/SignalSlot
---------------------------------------

How to register an EventHandler/SignalSlot depends if you are using a TYPO3
version below v10 or not.

.. _extending-events-register-psr14:

In TYPO3 v10 and above
^^^^^^^^^^^^^^^^^^^^^^

Register the EventHandler in the :file:`Configuration/Services.yaml` file like
all other PSR-14 EventHandlers:

.. code-block:: yaml

  Foo\Bar\EventListener\Hubspot\SkipFrontendUserSyncIfCountryIsUsaEventHandler:
    tags:
      - name: event.listener
        identifier: 'skipFrontendUserSyncIfCountryIsUsa'
        event: T3G\Hubspot\Service\Event\BeforeAddingFrontendUserToHubspotEvent

.. _extending-events-register-signal:

In TYPO3 v9 and below
^^^^^^^^^^^^^^^^^^^^^

Register your class as a SignalSlot. This extension provides a convenience
method for registering PSR-14-compatible event handlers as if they were
SignalSlots.

.. code-block:: php

   T3G\Hubspot\Utility\CompatibilityUtility::registerEventHandlerAsSignalSlot(
       \T3G\Hubspot\Service\Event\BeforeAddingFrontendUserToHubspotEvent,
       \Foo\Bar\EventListener\Hubspot\SkipFrontendUserSyncIfCountryIsUsaEventHandler
   );

Inserted into :file:`ext_tables.php`.

.. _extending-events-list:

List of available events
------------------------

All events and EventHandler interfaces have the namespace
:php:`T3G\Hubspot\Service\Event\*`.

.. _extending-events-list-contact:

Events in ContactSynchronizationService
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:php:`AfterAddingFrontendUserToHubspotEvent`
  EventHandler interface: :php:`AfterAddingFrontendUserToHubspotEventHandlerInterface`
:php:`AfterAddingHubspotContactToFrontendUsersEvent`
  EventHandler interface: :php:`AfterAddingHubspotContactToFrontendUsersEventHandlerInterface`
:php:`AfterContactSynchronizationEvent`
  EventHandler interface: :php:`AfterContactSynchronizationEventHandlerInterface`
:php:`AfterMappingFrontendUserToHubspotContactPropertiesEvent`
  EventHandler interface: :php:`AfterMappingFrontendUserToHubspotContactPropertiesEventHandlerInterface`
:php:`AfterMappingHubspotContactToFrontendUserEvent`
  EventHandler interface: :php:`AfterMappingHubspotContactToFrontendUserEventHandlerInterface`
:php:`AfterUpdatingFrontendUserAndHubspotContactEvent`
  EventHandler interface: :php:`AfterUpdatingFrontendUserAndHubspotContactEventHandlerInterface`
:php:`BeforeAddingFrontendUserToHubspotEvent`
  EventHandler interface: :php:`BeforeAddingFrontendUserToHubspotEventHandlerInterface`
:php:`BeforeAddingHubspotContactToFrontendUsersEvent`
  EventHandler interface: :php:`BeforeAddingHubspotContactToFrontendUsersEventHandlerInterface`
:php:`BeforeComparingFrontendUserAndHubspotContactEvent`
  EventHandler interface: :php:`BeforeComparingFrontendUserAndHubspotContactEventHandlerInterface`
:php:`BeforeContactSynchronizationEvent`
  EventHandler interface: :php:`BeforeContactSynchronizationEventHandlerInterface`
:php:`BeforeFrontendUserSynchronizationEvent`
  EventHandler interface: :php:`BeforeFrontendUserSynchronizationEventHandlerInterface`
:php:`BeforeMappingFrontendUserToHubspotContactEvent`
  EventHandler interface: :php:`BeforeMappingFrontendUserToHubspotContactEventHandlerInterface`
:php:`BeforeMappingHubspotContactToFrontendUserEvent`
  EventHandler interface: :php:`BeforeMappingHubspotContactToFrontendUserEventHandlerInterface`
:php:`BeforeUpdatingFrontendUserAndHubspotContactEvent`
  EventHandler interface: :php:`BeforeUpdatingFrontendUserAndHubspotContactEventHandlerInterface`
:php:`ResolveHubspotContactEvent`
  EventHandler interface: :php:`ResolveHubspotContactEventHandlerInterface`

.. _extending-events-list-customobject:

Events in CustomObjectSynchronizationService
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:php:`AfterAddingMappedTableRecordToHubspotEvent`
  EventHandler interface: :php:`AfterAddingMappedTableRecordToHubspotEventHandlerInterface`
:php:`BeforeAddingMappedTableRecordToHubspotEvent`
  EventHandler interface: :php:`BeforeAddingMappedTableRecordToHubspotEventHandlerInterface`
:php:`BeforeCustomObjectSynchronizationEvent`
  EventHandler interface: :php:`BeforeCustomObjectSynchronizationEventHandlerInterface`
