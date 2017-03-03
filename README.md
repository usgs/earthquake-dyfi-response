dyfi-responses
==============

The DYFI responses subsytem. This manages the transfer of
DYFI user entries from the web interface to the DYFI backend
servers through the hazdev acquisition servers.

Location
--------

To be run on the HazDev acquisitions servers.

Components
----------

This subsytem has two components:

1. response.php 

This program (found in the htdocs directory of this repo)
is run by the DYFI Questionnaire once the
user submits the form. It takes the form data and saves it as a unique
entry file (raw text format). It also displays the closing
message for the user.


2. replicate_incoming.php

This program takes each entry file and replicates it to one directory
for each of the backend servers. The entry files will be stored there
until the backend server periodically transfers the files to the backend
system. This program should be run in the background from crontab.


INSTALLATION AND CONFIGURATION
------------------------------

This subsytem should be installed in each "target" or response server for the DYFI Questionnaire. Setup requires the following locations:


- An 'apps' directory, [apps]/earthquake-dyfi-response. Executables go here. 
- A 'data' directory, [data]/earthquake-dyfi-response. Incoming entry files, replication directories, and logs go here. Ensure that it has sufficient file space.

1. Clone this repository into your apps directory:
    - [apps]/earthquake-dyfi-response

2. Ensure the DYFI Questionnaire form points to the correct executable: 
    - [apps]/earthquake-dyfi-response/.build/src/htdocs/responses.php

3. Obtain an ArcGISOnline account (https://www.arcgis.com) and note your ID and Secret Key.

4. cd to the repository root and run src/lib/pre-install to configure (this will create the target directories).

5. Add the replicate.sh script in the crontab (preferably running once per minute).

6. Make sure that the created directories are web-writeable.

TODO
----
- Set up Docker/NPM/Travis??

