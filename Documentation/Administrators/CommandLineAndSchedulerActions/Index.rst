Command Line and Scheduler Actions
==================================

The CLI commands are executed through the `standard TYPO3 CLI<https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/CommandControllers/Index.html#running-the-command-from-the-command-line>`__.

.. _command-hubspot-contactsync:
.. _command-hubspot-sync:

Synchronize Hubspot contacts and Frontend Users
-----------------------------------------------

`hubspot:sync [options] [--] [<types>...]`

This command synchronizes HubSpot records with TYPO3 records.

.. _command-hubspot-sync-arguments:

Arguments
~~~~~~~~~

Supply the types of synchronizations to run as command arguments. If none are
supplied, all types are used.

Available type arguments
^^^^^^^^^^^^^^^^^^^^^^^^

**contacts**
   Synchronize frontend users with Hubspot contacts.
**customobjects**
   Synchronize TYPO3 records of any type with Hubspot Custom Objects of any
   type.

.. _command-hubspot-sync-options:

Options
~~~~~~~

.. code-block::

  -p, --default-pid=DEFAULT-PID      Default PID for storage and TypoScript
                                     settings
  -s, --limit-to-pids=LIMIT-TO-PIDS  Array of PIDs to search within. Default
                                     is to ignore PID. (multiple values allowed)
  -l, --limit=LIMIT                  Max records to synchronize

