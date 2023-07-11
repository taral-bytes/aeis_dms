1.0.19 (2023-05-03)
---------------------
- add support for custom attributes
- add method SeedDMS_SQLiteFTS_Indexer::reindexDocument()
- SeedDMS_SQLiteFTS_Indexer::terms() can be passed an array of cols
- rename field 'users' to 'user'
- code clean up
- add sorting by modification time

1.0.18 (2023-01-09)
---------------------
- add optional parameter $order to SeedDMS_SQLiteFTS_Indexer::find()
- add optional parameters $query and $col to SeedDMS_SQLiteFTS_Indexer::terms()
- IndexedDocument() accepts a callable for conversion to text
- remove stop words from content

1.0.17 (2022-03-04)
---------------------
- throw exeption in find() instead of returning false
- fix query if rootFolder or startFolder is set
   

1.0.16 (2021-05-10)
---------------------
- close pipes in execWithTimeout(), also return exit code of command
- add support for fts5 (make it the default)
- add class SeedDMS_SQLiteFTS_Field

1.0.15 (2020-12-12)
---------------------
- add indexing folders

1.0.14 (2020-09-11)
---------------------
- add searching for document status
- search even if query is empty	(will find all documents)
- parameters for SeedDMS_SQLiteFTS_Search::search() has changed
- SeedDMS_Lucene_Search::search() returns array of hits, count and facets
- pass config array instead of index directory to SeedDMS_Lucene_Indexer::create()
  and SeedDMS_Lucene_Indexer::open()

1.0.13 (2020-09-02)
---------------------
- add user to list of terms

1.0.12 (2020-09-02)
---------------------
- Index users with at least read access on a document

1.0.11 (2019-11-28)
---------------------
- Set 'created' in index to creation date of indexed content (was set to current
timestamp)

1.0.10 (2018-04-11)
---------------------
- IndexedDocument() remembers cmd and mimetype

1.0.9 (2018-01-30)
---------------------
- execWithTimeout() reads data from stderr and saves it into error msg

1.0.8 (2017-12-04)
---------------------
- allow conversion commands for mimetypes with wildcards

1.0.7 (2017-03-01)
---------------------
- catch exception in execWithTimeout()

1.0.6 (2016-03-29)
---------------------
- fix calculation of timeout (see bug #269)

1.0.5 (2016-03-29)
---------------------
- set last parameter of stream_select() to 200000 micro sec. in case the timeout in sec. is set to 0

1.0.4 (2016-03-15)
---------------------
- make it work with sqlite3 &lt; 3.8.0

1.0.3 (2016-02-01)
---------------------
- add command for indexing postѕcript files

1.0.2 (2016-01-10)
---------------------
- check if index exists before removing it when creating a new one

1.0.1 (2015-11-16)
---------------------
- add __get() to SQLiteFTS_Document because class.IndexInfo.php access class variable title which doesn't exists

1.0.0 (2015-08-10)
---------------------
- initial release

