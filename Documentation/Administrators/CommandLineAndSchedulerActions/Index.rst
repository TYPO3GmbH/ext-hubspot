Command Line and Scheduler Actions
==================================

The CLI commands are executed through the `standard TYPO3 CLI<https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/CommandControllers/Index.html#running-the-command-from-the-command-line>`__.

.. _command-hubspot-contactsync:

Synchronize Hubspot contacts and Frontend Users
-----------------------------------------------

`hubspot:contactsync [options]`

This command synchronizes HubSpot contact records with TYPO3 frontend users.

Options
~~~~~~~

.. code-block::

   Options:
     -p, --default-pid=DEFAULT-PID      Default PID for storage and TypoScript
                                        settings
     -s, --limit-to-pids=LIMIT-TO-PIDS  Array of PIDs to search within. Default
                                        is to ignore PID. (multiple values
                                        allowed)
     -l, --limit=LIMIT                  Max records to synchronize
     -h, --help                         Display this help message
     -q, --quiet                        Do not output any message
     -V, --version                      Display this application version
         --ansi                         Force ANSI output
         --no-ansi                      Disable ANSI output
     -n, --no-interaction               Do not ask any interactive question
     -v|vv|vvv, --verbose               Increase the verbosity of messages: 1
                                        for normal output, 2 for more verbose
                                        output and 3 for debug

