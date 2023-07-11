1.6.0 (2023-04-13)
---------------------
- fix creation of raw preview images

1.5.0 (2023-01-09)
---------------------
- add previewer which creates txt

1.4.0 (2021-10-16)
---------------------
- use new conversion service if available
- createRawPreview() checks early if a converter exists
   

1.3.3 (2020-12-23)
---------------------
- close pipes in execWithTimeout(), also return exit code of command
- createPreview() has optional parameter by referenz to return true if a
  preview image was actually created

1.3.2 (2020-12-23)
---------------------
- set header Content-Length 
- update package description

1.3.1 (2020-03-21)
---------------------
- add parameter $target to SeedDMS_Preview_pdfPreviewer::hasRawPreview() and SeedDMS_Preview_pdfPreviewer::getRawPreview()

1.3.0 (2020-02-17)
---------------------
- add new methode getPreviewFile()

1.2.10 (2019-02-11)
---------------------
- new parameter for enabling/disabling xsendfile
- fix creation of pdf preview if document content class is not SeedDMS_Core_DocumentContent

1.2.9 (2018-07-13)
---------------------
- make sure list of converters is always an array
- usage of mod_sendfile can be configured

1.2.8 (2018-03-08)
---------------------
- preview is also created if SeedDMS_Core_DocumentContent has a child class

1.2.7 (2018-01-18)
---------------------
- add SeedDMS_Preview_Base::sendFile() as a replacement for readfile() which uses
- mod_xsendfile if available
- execWithTimeout() reads data from stderr and returns it together with stdout in array

1.2.6 (2017-12-04)
---------------------
- SeedDMS_Preview_Base::setConverters() overrides existing converters.
- New method SeedDMS_Preview_Base::addConverters() merges new converters with old ones.

1.2.5 (2017-10-11)
---------------------
- SeedDMS_Preview_Base::hasConverter() returns only try if command is set

1.2.4 (2017-10-11)
---------------------
- fix typo in converter for tar.gz files

1.2.3 (2017-09-18)
---------------------
- createPreview() returns false if running the converter command fails

1.2.2 (2017-03-02)
---------------------
- commands can be set for mimetypes 'xxxx/*' and '*'
- pass mimetype as parameter '%m' to converter

1.2.1 (2016-11-15)
---------------------
- setConverters() overrides exiting converters

1.2.0 (2016-11-07)
---------------------
- add new previewer which converts document to pdf instead of png

1.1.9 (2016-04-26)
---------------------
- add more documentation
- finish deletePreview()
- add new method deleteDocumentPreviews()
- fix calculation of timeout (Bug #269)
- check if cache dir exists before deleting it in deleteDocumentPreviews()

1.1.8 (2016-04-05)
---------------------
- pass variables to stream_select (required by php7)

1.1.7 (2016-03-29)
---------------------
- set last parameter of stream_select() to 200000 micro sec. in case the timeout in sec. is set to 0

1.1.6 (2016-03-08)
---------------------
- check if object passed to createPreview(), hasPreview() is not null

1.1.5 (2016-02-11)
---------------------
- add method getFilesize()
- timeout for external commands can be passed to contructor of SeedDMS_Preview_Previewer

1.1.4 (2015-08-08)
---------------------
- command for creating the preview will be called with a given timeout

1.1.3 (2015-02-13)
---------------------
- preview images will also be recreated if the object this image belongs is of newer date than the image itself. This happens if versions are being deleted and than a new version is uploaded. Because the new version will get the version number of the old version, it will also take over the old preview image.Comparing the creation date of the image with the object detects this case.

1.1.2 (2014-04-10)
---------------------
- create fixed width image with proportional height

1.1.1 (2014-03-18)
---------------------
- add converters for .tar.gz, .ps, .txt

1.1.0 (2013-04-29)
---------------------
- preview image can also be created from a document file (SeedDMS_Core_DocumentFile)

1.0.0 (2012-11-20)
---------------------
- initial version

