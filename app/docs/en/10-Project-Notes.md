# Project Notes

## Issues

### SQL Queries
Parts of core bypass the ORM and make direct queries, not sure to what extent this is a problem. This issue will likely affect third party modules.

### Joins
Relations need to move from using SQL "joins" to using "traverses". Traverse queries may not be as structured and easy to generate as joins. The data for the joins is stored in "containers" on each document, currently no support for transactions in the PHP connector to OrientDB so there may be an issue with updating a relation if it involves updating a number of documents.

Taking exisitng innerJoin() and leftJoin() methods on DataList and making them into meaningful traverses in OrientDB would be very difficult which is perhaps quite a large issue for third party modules that have made use of SQL joins.

__Update:__ Containers and traverses have been used for many_many components and the same approach could be used with other relation types.

### Inheritance
Class inheritance in Silverstripe results in multiple tables being created and joined. RIDs may need to be unique between tables, so this architecture will need to be reworked. Similar issues will exist for versioning, translatable etc.

__Update:__ Inheritance has been implemented.

### RIDs
RIDs are reused unless "plocal" storage type is specified. If a record is deleted in other storage types like "local" the RID may in fact be reused:  
http://jezzper.com/jezzper/discussions.nsf/0/9D9B9184D94E067CC1257BC2004EE04C

### Can only support document database (not graph)
When creating OrientDB database can specify "document" or "graph". Graph databases are essentially document databases where documents are the nodes and another object called vertices are used to traverse the graph etc. Currently the PHP OrientDB connector only has support for using OrientDB as a document store, not sure how to handle vertices if abilitiy to interact with them was available.

### IDs are strings (non numeric)
Several areas in core anticipate a numeric ID, currently the ID field is used to store a copy of the RID for the record which is in the format: x:y where x and y are numeric - x representing the cluster number and y representing the record number in that cluster. 

__Update:__ Has been implemented.

### DataList overriding
It is not sufficient to use Object::use\_custom\_class('DataList', 'OrientDataList') as there are subclasses of DataList. Using custom classes for all subclasses is cumbersome, costly on memory and projects may subclass DataList independently. DataList requires dependency injection for the data query and filter context at minimum, there is also custom SQL queries in DataList that need to be overriden (mostly to remove "s which may be able to be hacked into OrientDataQuery). Could possibly use different namespaces before instantiating these classes?

__Update:__ Has been implemented.

### Search filters
Numerous search filters need to be overridden, currently only ExactMatchFilter has been completed.


## Limitations

#### Cannot have many_many extra fields
Many_many relations are not represented with a join table but with containers on each class such as LinkSet. 

#### Clusters are not used/supported at this time
Not clear how clusters can fit in with SilverStripe, we can query from a cluster but creating clusters has not been attempted.

#### Graph entities such as vertices are not supported
Unsure when this will be available in the PHP connector we are using for this work.

#### Where, sort and limit filters on getManyManyComponents
Cannot filter the traverse command particularly easily

#### Only select * is currently supported effectively
Cannot select multiple different fields or select DISTINCT currently


## TODOS

#### ManyManyList
remove() removeByID() removeAll()

#### getComponent() getComponents()
DataObject getters for has\_one, has\_many, belongs\_to altered to use Link and LinkSet

#### Search filters
SearchFilter classes need to be upgraded for ModelAdmin to work

#### Versioned architecture
Some sort of solution for versioned

#### Pagination support
Pagination does not currently work, needs a [new query structure](https://github.com/orientechnologies/orientdb/wiki/Pagination).






