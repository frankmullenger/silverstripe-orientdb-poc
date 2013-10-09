# OrientDB Notes

## DB Architecture

### Active Record

Class maps to the table in the DB, each instance of the class is a row in the DB.  
Encapsulated the database access and adds domain logic on that data.  
Constructs an instance from an SQL result.  
Updates the databases.

Problems with ActiveRecord for NoSQL:  
Could have multiple classes accessing one collection depending on the data

### Data mapper

An extra layer of Mappers that moves data between objects and a database while keeping them independant of each other and the mapper itself.

Data mapper is a layer of software that separates the in-memory objects from the database. This is especially useful for NoSQL because the mapper can choose the class depending on the data (ClassName in the data).

Because database does not validate this is solely the responsibility of the domain model.

Attribute of the model could also be another model.

Better support for denormalization.

### Record ID (RID)

RIDs are in the format #<cluster-id>:<cluster-position>

RID is the physical location of the record inside the database, so lookups are very fast O(1) rather than O(log n) for RDBMS (need to confirm actual big O notation for this claim).

### Classes

Similar to classes in OO paradigm.

* schema-full - having strong typed mandatory properties
* schema-less (no defined properties)
* mixed-mode - some properties could be defined and strongly typed, others without a type or even not defined

A schema less or mixed class can have fields populated which have not previously been declared e.g:  
INSERT INTO Posts (title,text) VALUES ("some title","some text")

In this case title and text fields were not declared as part of the schema.

create property Posts.pubDate date

This has created the property "pubDate" in the schema so now Posts class is mixed-mode. If we now look at structure of Posts it will only have "pubDate" as the title and text fields are not officially part of the structure.

To "force" a schema:  
ALTER CLASS Posts STRICTMODE true

So now we cannot just enter data from whatever fields we like e.g text or title. Fields need to be part of the schema. The old records for title and text are still there.

Can alter properties using ALTER PROPERTY ... need to alter one at a time.

### Abstract Classes

Abstract classes not associated to any cluster (cluster ID -1)  
Cannot store any records  
Useful for defining properties which every derived class will have

__Note:__ Class names are NOT case sensitive, but property names ARE case sensitive.

### Users

Server users are in the XML config file, they are special users and have access to all databases on the server.

Database users are entered in the OUsers table and need roles created for them to give them permissions on particular databases.

__Note:__ Best to leave the users in the XML config file as they are used by OrientDB.

OrientDB has record level security that can be used to restrict access to records - could map to SilverStripe Members and roles perhaps.

__Note:__ User passwords are hashed with SHA-256 by default, SilverStripe uses Blowfish for Members table.

### Relation Management

https://github.com/orientechnologies/orientdb/wiki/Tutorial%3A-Relationships  
https://github.com/orientechnologies/orientdb/wiki/SQL-Create-Link

#### Embedded documents

Associate to a property any valid document object expressed as a valid JSON string.  
Embedded documents have no RIDs and they live withing the scope of the parent record. If you delete teh parent record its embedded records get deleted.

#### Containers

Special fields that can contain a set of other fields.  
3 kinds of containers, each can contain embedded documents or RIDs that point to non-embedded records.

RIDs contained in record fields are also called LINKs.

* set - unordered set of elements, array is unique
* list - ordered sequence of elements, can have duplicates
* map - set of key/value pairs where keys are strings and values can be any of the allowed values, even other continers

To identify a container value you must use the square brackets like JSON

Need to consider how the data is queried, OrientDB very quick at querying relations but only in one direction, if the queries are likely in the other direction may need to use a LinkSet or similar on the other object for instance.

## Querying

SELECT [<Projections>] [FROM <Target> [LET <Assignment>*]]
  [WHERE <Condition>*]
  [GROUP BY <Field>*]
  [ORDER BY <Fields>* [ASC|DESC] *]
  [SKIP <SkipRecords>]
  [LIMIT <MaxRecords>]
  [FETCHPLAN <FetchPlan>]

Target can be set to a:
* class
* cluster
* RID
* set of RIDs

If you want to perform a query on a single RID or set of them you can perform the query directly on it/them - much faster than a query on a class.

SELECT * FROM #23:1  
much faster than  
SELECT * FROM Posts WHERE @rid = #23:1

### RIDs in queries

SELECT * FROM Posts  
Will return the RID, title, text and other properties, where RIDs are #10:0, #10:1, #10:2 etc.

SELECT title FROM Posts  
Will set the RIDs to -2:1, -2:2, -2:3 etc.

So the RIDs in the second query are incorrect.

The RIDs are not simply logical identifiers, they are physical pointers to a file indicating where the records' data begins.

When you execute a SELECT query providing a projection the returned records aren't the ones stored on files bt they are _virtual records_ bult at the runtime and have no physical pointers. In these cases OrientDB returns negative RIDs.

To obtain the record RIDs in these queries use special @rid property:  
SELECT @rid as RealRid, title FROM Posts

### Special properties

* @rid = RID of the entry
* @class = the class of the record (ClassName equivalent)
* @version = version number of the record. Every time record is update it's version number changes.
* @size = size of record in bytes
* @type = the record type, can be document or binary. Binary records are used to store blob objects.
* @this = specifies the record itself

## Commands

https://github.com/orientechnologies/orientdb/wiki

### Scripts and configuration

cd ~/Scripts/orientdb-graphed-1.5.0/bin/ = location of OrientDB binaries  
./server.sh = start the OrientDB server  
./shutdown.sh = stop the OrientDB server  
./console.sh = to perform queries etc.

__Note:__  
OrientDB Studio  
Can browser to localhost:2480 after server started for web interface.

~/Scripts/orientdb-graphed-1.5.0/config/orientdb-server-config.xml = configuration for users etc.

### Connect to server and databases

connect remote:localhost root <some pasword> = connect to remote server  
connect remote:localhost/demo admin admin = connect to remote database  
connect plocal:../databases/demo admin admin = connect to local database

### Create databases

list databases  = list the databases on this server  
create database remote:localhost/minimalblog root <root password> local document = create a new document database on local storage  
create database plocal:../databases/demo admin admin plocal graph = create a new graph database using plocal storage  

### Create classes (tables)

classes = list all classes in the current database  
create class Student = create a new class  
create class Person abstract = create a new abstract class  
create class Student extends Person = create concrete class from abstract class  
info class Student / desc Student = display details about the Student class such as the structure  

### Create properties

create property Student.FirstName string = create a new string property on Student class  
alter property Student.FirstName min 3 = adding constraint minimum 3 characters  

#### Links

create property Page.Image link File = creating a link in the Page class -> has_one: Image => File  
insert into Page (Title,Image) values ("Page Two",#14:0) = inserting a link

traverse Image from Page = go through pages table and follow any fields called "Image" to retrieve the related record basically  
traverse all() from Page = go through pages table and follow all fields  
traverse any() from Page = go through pages table and follow any fields  

##### Context variables

Variables you can add to the results of the traverse that will give some information on the result set.

* $parent = parent of actual record
* $current = retrieves current record
* $depth = current depth of nesting
* $path = path of current record from root of traversing

select $path from (traverse Image from Page) = get the path for a traverse  
select Image.Name from (traverse Image from Page) = get the image name from links on page

### Configuration changes

set limit 100 = increase default limit for browse queries to 100

## Insert records

insert into posts (title,text) values ("title 1","text 1"), ("title 2","text 2"), ("title 3","text 3") = insert records

## Deleting records

truncate class <class name> = empty a table, can also truncate record to delete a record or truncate cluster

__Note:__ When deleting a record its RID becomes available to be reassigned to a new record, so need to pay attention managing deletions.

### Browse records

browse class OUser = display records in the class  
clusters = list the clusters  
load record #5:0 = load a record for inspecting it  


