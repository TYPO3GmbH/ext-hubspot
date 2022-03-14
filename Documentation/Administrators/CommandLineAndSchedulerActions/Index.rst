Command Line and Scheduler Actions
==================================

The CLI command is executed through the
`standard TYPO3 CLI<t3tsref:running-the-command-from-the-command-line>`__.

You can also execute the command through the Scheduler.

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

.. code-block:: text

  -p, --default-pid=DEFAULT-PID      Default PID for storage and TypoScript
                                     settings
  -s, --limit-to-pids=LIMIT-TO-PIDS  Array of PIDs to search within. Default
                                     is to ignore PID. (multiple values allowed)
  -l, --limit=LIMIT                  Max records to synchronize

