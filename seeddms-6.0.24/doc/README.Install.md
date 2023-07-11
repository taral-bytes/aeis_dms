SeedDMS Installation Instructions
==================================

REQUIREMENTS
============

SeedDMS is a web-based application written in PHP. It uses MySQL,
SQLite3 or PostgreSQL to manage the documents that were uploaded into
the application. Be aware that PostgreSQL is not very well tested.

Make sure you have PHP >= 7.3 and MySQL 5 or higher installed. SeedDMS
will work with PHP running in CGI-mode as well as running as a module under
apache.

Here is a detailed list of requirements:

1. A web server with at least php 7.3
2. A mysql database, unless you use SQLite
3. The php installation must have support for `pdo_mysql`, `pdo_pgsql` or `pdo_sqlite`,
   `php_gd2`, `php_mbstring`, `php_xml`
4. Depending on the configuration the extensions `php_ldap`, `php_mycrypt`,
   `php_gmp`, `php_libsodium` must be installed
5. Various command line programms to convert files into text for indexing
   pdftotext, catdoc, xls2csv or scconvert, cat, id3 (optional, only needed
   for fulltext search)
6. ImageMagic (the convert program) is needed for creating preview images 
7. A bunch of packages from Packagist which all ship with the seeddms-quickstart
   archive

It is highly recommended to use the quickstart archive
(seeddms-quickstart-x.y.z.tar.gz) because it includes all software packages
(excluding those listing above in item 1. to 6.) for running SeedDMS. Hence,
you still need a working web server with PHP and in addition a mysql or
PostgreSQL database unless you intend to use SQLite.

QUICKSTART
===========

The fastes way to get SeedDMS running is by unpacking the archive
`seeddms-quickstart-x.y.z.tar.gz` on your webserver.
Let's assume you use seeddms-quickstart-5.1.x.tar.gz.
It will create a new directory `seeddms51x` containing everything you
need to run SeedDMS with SQLite3. Even if you intend to use mysql in the
long run it is advisable to first set up SeedDMS with SQLite3 and than
just switch the database.

Setting up the web server
--------------------------

First of all you will need to set up your web server. Here, we will only focus
on apache running on Debian/GNU Linux.
Either let the document root of your web server point to the directory `www`
below `seeddms51x`

DocumentRoot /var/www/seeddms51x/www

or add an alias. For apache this could be like

Alias /seeddms51x /<some directory>/seeddms51x/www

or even

Alias /mydms /<some directory>/seeddms51x/www

Do not set the DocumentRoot or Alias to
the `seeddms51x` directory, because this will allow anybody to access
your `data` and `conf` directory if it is not secured by a .htaccess file.
This is a major security risk.

Make sure that the subdirectory `seeddms51x/data` and the configuration file
`seeddms51/conf/settings.xml` is writeable by your web server. All other
directories can be just readable by your web server, though it is advisable
to even protect them from writing.

Adjusting the configuration of SeedDMS
---------------------------------------

In the next step you need to adjust the configuration file in
`seeddms51x/conf/settings.xml`. Open the file in your favorite text editor
and search for `/home/wwww-data`. Replace that part in any path found with your
base directory where you placed seeddms51x (e.g. /var/www/html/seeddms51x).
Alternatively, you can open the installer with a browser at
http://your-domain/install (if the document root points to
`seeddms51x/www`) or http://your-domain/seeddms51x/install/ (if you have
set an alias like described above).

It will first ask to unlock the installer by creating a file
`ENABLE_INSTALL_TOOL` in the diretory `seeddms51x/conf/`. Change all paths by
replacing `/home/www-data` with your base directory where you put seeddms51x.
Set httpRoot to `/` (if  the document root points to `seeddms51x/www`) or
`/seeddms51x` (if you have set an alias `seeddms51x` like described above).

Once your configuration is done,
save it, remove the file `ENABLE_INSTALL_TOOL` and point your browser to
http://your-domain/ or http://your-domain/seeddms51x.

SECURITY CONSIDERATIONS
=======================

You should always access your SeedDMS installation through
a secured https connection, unless you precisly know what you are doing.
SeedDMS ships an .htaccess file which already has some common security
http headers set. In order for them to apply you need to activate the
headers module. On Debian/GNU Linux this can be done with

```
a2enmod headers
```

Protect directories with data or configuration
---------------------------------------------

A crucial point when setting up SeedDMS is the propper placement of the
data directory. Do not place it below your document root of your web server!
If you do so, there is a potential way that
attackers can easily access your documents with a regular web browser.
If you cannot place the data directory outside of document root, then either
restrict access to it with an appropriate `.htaccess` file like the following.
The SeedDMS quickstart archive already includes this `.htaccess` file.

```
# line below if for Apache 2.4
<ifModule mod_authz_core.c>
Require all denied
</ifModule>

# line below if for Apache 2.2
<ifModule !mod_authz_core.c>
deny from all
Satisfy All
</ifModule>

# section for Apache 2.2 and 2.4
<ifModule mod_autoindex.c>
IndexIgnore *
</ifModule>
```

Alternatively or in addition you can change
the `contentOffsetDir` in `settings.xml` to something random, but ensure it
is still a valid directory name. If you change contentOffsetDir, then
do not forget to move `data/1048576` to `data/<your random name>`.
Also turn off directory listings in your apache configuration for the
`data` directory.


Securing the configuration file
---------------------------------

The configuration can be fully controlled by any administrator of SeedDMS. This
can be crucial for those configuration options where external commands are
being configured, e.g. for the full text engine or creating preview images.
As a hoster you may not want this configuration options being set by a SeedDMS
administrator. For now you need to make the configuration file `settings.xml`
unwritable for the web server. In that case the SeedDMS administrator can
still see the configuration but will not be able to change it.

Since version 5.1.23 and 6.0.16 of SeedDMS there is some preliminary way to
hide parts of the configuration which makes them unchangeable for the
SeedDMS administrator.

Setting a new encryption key
------------------------------

Though this is not related to setting up the web server environment, it is
important to recreated the encryption key in SeedDMS once SeedDMS is running.
Just open the settings in the admin tools and empty the currently set
encryption key on the tab 'System'. Save the settings and check the key again.
It should be a new one. Save the settings again. The encryption key is
mainly used for creating tokens in HTML forms to prevent CSRF attacks.

UPGRADING FROM A PREVIOUS VERSION OF SEEDDMS
=============================================

As SeedDMS is a smooth continuation of LetoDMS there is no difference
in updating from LetoDMS or SeedDMS.

You have basically two choices to update SeedDMS:

- you install a fresh version of SeedDMS and copy over your data and configuration
- you replace the software in your current installation with a new version

The first option is less interuptive but requires to be able to set up a second
temporary SeedDMS installation, which may not be possible, e.g. because of storage
limitations. It can be the only option if you change servers.

The first update procedure is only needed if the version changes on the minor
or major version number. Changes in the subminor version number will never
include database changes and consequently it is sufficient to use the existing
data directory and database with the new version. Choose the second update
option in this case.

In both cases make sure to have a backup of your data directory, configuration
and database.

Fresh installation and take over of data
-----------------------------------------

The first update option is to set up a new instance of SeedDMS and once
that is running take over the data from your current (old) instance.

1. just do a fresh installation somewhere on your web server and make sure it
   works. It is fine to use
   SQLite for it, even if your final installation uses MySQL.
2. replace the data directory in your new installation with the data directory
   from your current installation. Depending on the size of that directory (and
   whether the new installation is on a new server or the old server) you
   may either copy, move or place a symbolic link. The content of the data directory
   will not be changed during the update. Its even perfectly save to
   browse through your documents and download them after finishing the
   update. The data directory will not be modified until you actually modify
   documents.
3. copy over the configuration `settings.xml` into your new installation. This will
   effectively make your new installation use the data from your old installation,
   because all paths are still pointing to the old installation.
4. if you use mysql you could as well make a copy of the database to make sure
   your current database remains unchanged.
5. modify the `settings.xml` to fit the environment of the new installation.
   This will mostly be the
   httpRoot, the paths to the installation directory and possibly the database
   connection.
6. create a file `ENABLE_INSTALL_TOOL` in the `conf` directory and point
   your browser at http://hostname/seeddms/install
   The install tool will detect the version of your current SeedDMS installation
   and run the required database updates.
   If you update just within the last version number (e.g. from 5.1.6 to 5.1.9),
   this step
   will not be required because such a subminor version update will never
   contain database updates.

Upgrading your current installation
-----------------------------------

Instead of setting up a new installation, you may as well replace the php files
in your current installation with new versions from the quickstart archive.

1. get the SeedDMS quickstart archive `seeddms-quickstart-x.y.z.tar.gz` and
   unpack it somewhere on your disc.
2. copy the directory `seeddms-x.y.z` from the unpacked archive into your
   current installation and make the link `seeddms` point to this new directory.
3. copy the directory `pear` from the unpacked archive into your current
   installation, replacing the existing directory. Make a backup of `pear` before
 	 the replacement if you want to ensure to be able to go back to your old version.
4. you may compare your `conf/settings.xml` file with the shipped version
   `conf/settings.xml.template` for new parameters. If you don't do it, the next
   time you save the configuration the default values will be used.
5. create a file `ENABLE_INSTALL_TOOL` in the `conf` directory and point
   your browser at http://hostname/seeddms/install
   The install tool will detect the version of your current SeedDMS installation
   and run the required database updates.
   If you update just within the last version number (e.g. from 5.1.6 to 5.1.9),
   this step
   will not be required because such a subminor version update will never
   contain database updates.


THE LONG STORY
================

This section is mostly outdated but may still contain some valueable
information for those trying to understand the installation process.

If you intend to run a single instance of SeedDMS, you are most likely
better off by using the quickstart archive as described above. This
section is mostly for users who wants to know more about the internals
of SeedDMS or do packaging for a software distribution, which already
ships some of the additional software SeedDMS requires.

SeedDMS has changed its installation process with version 3.0.0. This gives
you many more options in how to install SeedDMS. First of all, SeedDMS was
split into a core package (`SeedDMS_Core-<version>.tar.gz`) and the web
application itself (`seeddms-<version>.tar.gz`). The core is a pear package
which could be installed as one. It is responsible for all the database
operations. The web application contains the ui not knowing anything about
the database layout. Second, one SeedDMS installation can be used for
various customer instances by sharing a common source. Starting with
version 3.2.0 a full text search engine has been added. This requires
the zend framework and another pear package `SeedDMS_Lucene-<version>.tar.gz`
which can be downloaded from the SeedDMS web page. Version 4.0.0 show
preview images of documents which requires `SeedDMS_Preview-<version>.tar.gz`.
Finally, SeedDMS has
got a web based installation, which takes care of most of the installation
process.

Before you proceed you have to decide how to install SeedDMS:
1. with multiple instances
2. as a single instance

Both have its pros and cons, but
1. setting up a single instance is easier if you have no shell access to
   the web server
2. the installation script is only tested for single instances

Installation for multiple instances shares the same source by many
instances but requires to create links which is not in any case possible
on your web server.

0. Some preparation
-------------------

A common source of problems in the past have been the additional software
packages needed by SeedDMS. Those are the PEAR packages `Log`, `Mail` and
`HTTP_WebDAV_Server` as well as the `Zend_Framework`.
If you have full access to the server running a Linux distribution it is
recommended to install those with your package manager if they are provided
by your Linux distribution. If you cannot install it this way then choose
a directory (preferable not below your web document root), unpack the
software into it and extend the php include path with your newly created
directory. Extending the php include can be either done by modifying
php.ini or adding a line like

> php_value include_path '/home/mypath:.:/usr/share/php'

to your apache configuration or setting the `extraPath` configuration
variable of SeedDMS.

For historical reasons the path to the SeedDMS_Core and SeedDMS_Lucene package
can still be set
in the configuration, which is not recommend anymore. Just leave those
parameters empty.

On Linux/Unix your web server should be run with the environment variable
LANG set to your system default. If LANG=C, then the original filename
of an uploaded document will not be preserved if the filename contains
non ascii characters.

Turn off magic_quotes_gpc in your php.ini, if you are using a php version
below 5.4.

1. Using the installation tool
------------------------------

Unpack seeddms-<version>.tar.gz below the document root of
your web server.
Install `SeedDMS_Preview-<version>.tar.gz` and
`SeedDMS_Core-<version>.tar.gz` either as a regular pear package or
set up a file system structure like pear did somewhere on you server.
For the full text search engine support, you will also
need to install `SeedDMS_Lucene-<version>.tar.gz`.

For the following instructions we will assume a structure like above
and seeddms-<version> being accessible through
http://localhost/seeddms/

* Point you web browser towards http://hostname/seeddms/install/

* Follow the instructions on the page and create a file `ENABLE_INSTALL_TOOL`
  in the `conf` directory.

* Create a data directory with the thre sub directories staging, cache
  and lucene.
  Make sure the data directory is either *not* below your document root
	or is protected with a .htaccess file against web access. The data directory
  needs to be writable by the web server.

* Clicking on 'Start installation' will show a form with all necessary
  settings for a basic installation.

* After saving your settings succesfully you are ready to log in as admin and
  continue customizing your installation with the 'Admin Tools'

2. Detailed installation instructions (single instance)
-------------------------------------------------------

You need a working web server with MySQL/PHP5 support and the files
`SeedDMS-<version>.tar.gz`, `SeedDMS_Preview-<version>.tar.gz` and
`SeedDMS_Core-<version>.tgz`. For the 
full text search engine support, you will also need to unpack
`SeedDMS_Lucene-<version>.tgz`.

* Unpack all the files in a public web server folder. If you're working on
  a host machine your provider will tell you where to upload the files.
  If possible, do not unpack the pear packages `SeedDMS_Core-<version>.tgz`,
	`SeedDMS_Preview-<version>.tgz` and
  `SeedDMS_Lucene-<version>.tgz` below the document root of your web server.
	Choose a temporary folder, as the files will be moved in a second.

  Create a directory e.g. `pear` in the same directory where you unpacked
  seeddms and create a sub directory SeedDMS. Move the content except for the
  `tests` directory of all SeedDMS pear
  packages into that directory. Please note that `pear/SeedDMS` may not 
  (and for security reasons should not) be below your document root.
  
  You will end up with a directory structure like the following

  > seeddms-<version>
  > pear
  >   SeedDMS
  >     Core.php
  >     Core
  >     Lucene.php
  >     Lucene
  >     Preview
  >     Preview.php

  Since they are pear packages they can also be installed with

	> pear install SeedDMS_Core-<version>.tgz
	> pear install SeedDMS_Lucene-<version>.tgz
	> pear install SeedDMS_Preview-<version>.tgz

* The PEAR packages Log and Mail are also needed. They can be downloaded from
  http://pear.php.net/package/Log and http://pear.php.net/package/Mail.
	Either install it as a pear package
	or place it under your new directory 'pear'

  > pear
	>   Log
	>   Log.php
	>   Mail
	>   Mail.php

* The package HTTP_WebDAV_Server is also needed. It can be downloaded from
  http://pear.php.net/package/HTTP_WebDAV_Server. Either install it as a
	pear package or place it under your new directory 'pear'

  > pear
  >   HTTP
	>     WebDAV
	>       Server
	>       Server.php

  If you run PHP in CGI mode, you also need to place a .htaccess file
	in the webdav directory with the following content.

	RewriteEngine on
	RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]

* Create a data folder somewhere on your web server including the subdirectories
  staging, cache and lucene and make sure they are writable by your web server,
  but not accessible through the web.

For security reason the data folder should not be inside the public folders
or should be protected by a .htaccess file. The folder containing the
configuration (settings.xml) must be protected by an .htaccess file like the
following.

	> <Files ~ "^settings\.xml">
	> Order allow,deny
	> Deny from all
	> </Files>


If you install SeedDMS for the first time continue with the database setup.

* Create a new database on your web server
  e.g. for mysql:
	create database seeddms;
* Create a new user for the database with all permissions on the new database
  e.g. for mysql:
	grant all privileges on seeddms.* to seeddms@localhost identified by 'secret';
	(replace 'secret' with you own password)
* Optionally import `create_tables-innodb.sql` in the new database
  e.g. for mysql:
	> cat create_tables-innodb.sql | mysql -useeddms -p seeddms
  This step can also be done by the install tool.
* create a file `ENABLE_INSTALL_TOOL` in the `conf` directory and point
  your browser at http://hostname/seeddms/install


3. Email Notification
---------------------

A notification system allows users to receive an email when a
document or folder is changed. This is an event-based mechanism that
notifies the user as soon as the change has been made and replaces the
cron mechanism originally developed. Any user that has read access to a
document or folder can subscribe to be notified of changes. Users that
have been assigned as reviewers or approvers for a document are
automatically added to the notification system for that document.

A new page has been created for users to assist with the management of
their notification subscriptions. This can be found in the "My Account"
section under "Notification List".


4. Nearly finished
------------------

Now point your browser to http://hostname/seeddms/index.php
and login with "admin" both as username and password.
After having logged in you should first choose "My Account" and
change the Administrator's password and email-address.


CONFIGURING MULTIPLE INSTANCES
==============================

Since version 3.0.0, SeedDMS can be set up to run several parallel instances
sharing the same source but each instance has its own configuration. This is
quite useful if you intend to host SeedDMS for several customers. This
approach still allows to have diffenrent version of SeedDMS installed
and will not force you to upgrade a customer instance, because other
instances are upgraded. A customer instance consists of
1. a directory containing mostly links to the SeedDMS source and a
   configuration file
2. a directory containing the document content files
3. a database

1. Unpack the SeedDMS distribution
----------------------------------

Actually there is no need to set up the database at this point but it won't
hurt since you'll need one in the next step anyway. The sources of SeedDMS
can be anywhere you like. The do not have to be in you www-root. If you just
have access to your www-root directory, then put them there.

2. Setup the instance
---------------------

Unpack the files as described in the quick installation.

Create a directory in your www-root or use www-root for your instance. In the
second case, you will not be able to create a second instance, because each
instance needs its own directory.

Go into that directory create the following links (<seeddms-source> is the
directory of your initial SeedDMS intallation).

> src -> <seeddms-source>
> inc -> src/inc
> op -> src/op
> out -> src/out
> js -> src/js
> views -> src/views
> languages -> src/languages
> styles -> src/styles
> themes -> src/themes
> install -> src/install
> index.php -> src/index.php

> ln -s ../seeddms-<version> src
> ln -s src/inc inc
> ln -s src/op op
> ln -s src/out out
> ln -s src/js js
> ln -s src/views views
> ln -s src/languages languages
> ln -s src/styles styles
> ln -s src/themes themes
> ln -s src/install install
> ln -s src/index.php index.php

Create a new directory named conf and run the installation tool.

Creating the links as above has the advantage that you can easily switch
to a new version and go back if it is necessary. You could even run various
instances of SeedDMS using different versions.

3. Create a database and data store for each instance
-----------------------------------------------------

Create a database and data store for each instance and adjust the database
settings in conf/settings.xml or run the installation tool.

Point your web browser towards the index.php file in your new instance.

NOTE FOR VERSION 4.0.0
======================

Since version 4.0.0 of SeedDMS installation has been simplified. 
ADOdb is no longer needed because the database access is done by
PDO.

IMPORTANT NOTE ABOUT TRANSLATIONS
=================================

As you can see SeedDMS provides a lot of languages but we are not professional 
translators and therefore rely on user contributions.

If your language is not present in the login panel:
- copy the language/English/ folder and rename it appropriately for your
  language
- open the file `languages/your_lang/lang.inc` and translate it
- open the help file `languages/your_lang/help.htm` and translate it too

If you see some wrong or not translated messages:
- open the file `languages/your_lang/lang.inc`
- search the wrong messages and translate them

if you have some "error getting text":
- search the string in the english file `languages/english/lang.inc`
- copy to your language file `languages/your_lang/lang.inc`
- translate it

If there is no help in your language:
- Copy the English help `english/help.htm` file to your language folder
- translate it

If you apply any changes to the language files please send them to the
SeedDMS developers <info@seeddms.org>.

http://www.iana.org/assignments/language-subtag-registry has a list of
all language and country codes.

LICENSING
=========

SeedDMS is licensed unter GPLv2

Uwe Steinmann <info@seeddms.org>
