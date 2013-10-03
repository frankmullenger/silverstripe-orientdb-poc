# Project Notes

## Goals

1. SilverStripe works with OrientDB, one of the only CMS's to work with OrientDB.
2. See if OrientDB is going to be a good backend for SilverStripe.

## Initial notes from meeting

Want to strip out everything and keep it very simple, aim for a basic DataObject as a demo.  
No relations.  
Basic CRUD working.

Eventually want OrientDB to extend DB, have a look at MySQLDB, PostgresDB and SQLiteDB.

Would not be a bad idea to remove many many and replace with has many through.

Regex to add quotes to the manipulation.

Perhaps want to record the current schema of the DB in the database itself or some kind of cache.

/dev/build will no do much, might create the classes/tables: Create class Student (was Create table Student)

How to handle subclasses, currently subclasses have a seperate table.

Documents without a ClassName field should just be ignored.

DataMapper rather than ActiveRecord.

SearchFilter has a bunch of join logic that can probably be commented out.

Approach:  
silverstripe-orientdb module  
composer dependency of https://github.com/AntonTerekhov/OrientDB-PHP  
Fork framework  
Start with framework only and no CMS

Can RID  xx:xx go into the numeric ID field? - this would be ideal

## Issues

#### SQL Queries
Parts of core bypass the ORM and make direct queries, not sure to what extent this is a problem. This issue will likely affect third party modules.

#### Joins
Relations need to move from using SQL "joins" to using "traverses". Traverse queries may not be as structured and easy to generate as joins. The data for the joins is stored in "sets" on each document, currently no support for transactions in the PHP connector to OrientDB so there may be an issue with updating a relation if it involves updating a number of documents.

#### Inheritance
Class inheritance in Silverstripe results in multiple tables being created and joined. RIDs may need to be unique between tables, so this architecture will need to be reworked. Similar issues will exist for versioning, translatable etc.

#### RIDs
RIDs are reused unless "plocal" storage type is specified. If a record is deleted in other storage types like "local" the RID may in fact be reused:  
http://jezzper.com/jezzper/discussions.nsf/0/9D9B9184D94E067CC1257BC2004EE04C

#### Can only support document database (not graph)
When creating OrientDB database can specify "document" or "graph". Graph databases are essentially document databases where documents are the nodes and another object called vertices are used to traverse the graph etc. Currently the PHP OrientDB connector only has support for using OrientDB as a document store, not sure how to handle vertices if abilitiy to interact with them was available.

#### IDs are strings (non numeric)
Several areas in core anticipate a numeric ID, currently the ID field is used to store a copy of the RID for the record which is in the format: x:y where x and y are numeric - x representing the cluster number and y representing the record number in that cluster. It may be preferable to store an integer in the ID field removing the colon e.g: xy. Then need to overload the calls to DO->ID ->getID() etc. with __call() __get() which may be fiddly and might not prevent that many core hacks.




