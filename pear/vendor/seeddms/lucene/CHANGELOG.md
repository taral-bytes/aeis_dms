1.1.19 (2023-05-02)
---------------------
- use attribute definition id for field names of custom attributes
- add SeedDMS_Lucene_Indexer::reindexDocument()

1.1.18 (2023-01-09)
---------------------
- IndexedDocument() accepts a callable for conversion to text
- SeedDMS_Lucene_Search::open and create return itself but Zend_Search_Lucene

1.1.17 (2021-05-10)
---------------------
- close pipes in execWithTimeout(), also return exit code of command
   

1.1.16 (2020-12-12)
---------------------
- add indexing of folders

1.1.15 (2020-09-10)
---------------------
- add searching for document status
- better error handling if opening index fails	
- parameters for SeedDMS_Lucene_Search::search() has changed
- SeedDMS_Lucene_Search::search() returns array of hits, count and facets
- pass config array instead of index directory to SeedDMS_Lucene_Indexer::create()
  and SeedDMS_Lucene_Indexer::open()

1.1.14 (2020-09-02)
---------------------
- Index users with at least read access on the document

1.1.13 (2018-04-11)
---------------------
- IndexedDocument() remembers cmd and mimetype

1.1.12 (2018-01-30)
---------------------
- execWithTimeout() reads data from stderr and saves it into error msg

1.1.11 (2017-12-04)
---------------------
- allow conversion commands for mimetypes with wildcards

1.1.10 (2017-03-01)
---------------------
- catch exception in execWithTimeout()

1.1.9 (2016-04-28)
---------------------
- pass variables to stream_select() to fullfill strict standards.
- make all functions in Indexer.php static

1.1.8 (2016-03-29)
---------------------
- set last parameter of stream_select() to 200000 micro sec. in case the timeout in sec. is set to 0

1.1.7 (2016-02-01)
---------------------
- add command for indexing postѕcript files

1.1.6 (2015-08-05)
---------------------
- run external commands with a timeout

1.1.5 (2014-07-30)
---------------------
- field for original filename is treated as utf-8
- declare SeeDMS_Lucene_Indexer::open() static

1.1.4 (2013-08-13)
---------------------
- class SeedDMS_Lucene_Search::search returns false if query is invalid instead of an empty result record

1.1.3 (2013-06-27)
---------------------
- explicitly set encoding to utf-8 when adding fields
- do not check if deleting document from index fails, update it in any case

1.1.2 (2013-06-17)
---------------------
- parse query term and catch errors before using it

1.1.1 (2012-12-03)
---------------------
- catch exception if index is opened but not available

1.1.0 (2012-11-06)
---------------------
- use a configurable list of mime type converters, fixed indexing and searching
  of special chars like german umlaute.

1.0.1 (2011-11-06)
---------------------
- New Release

0.0.1 (2009-04-27)
---------------------

