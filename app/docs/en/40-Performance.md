# Performance

## Performance comparison

Included in this PoC is a performance controller. A useful way to use this is with virtual hosts pointing to the same instance of the site, then connecting to different databases depending on the ```$_SERVER['HTTP_HOST']``` in _config.php.

The performance controller can be accessed from yoursite.com/performance

The controller is very rudimentary, it only runs processes once, it is intended as a very basic indication of the areas where systems have potential.

### Usage

1. Build - this will clear all the data from the DB and ensure the structure is built correctly
2. Populate - creates a bunch of records
3. Select from the different actions such as Get One, Has One, Has Many etc. each will run slightly different code which will be displayed on screen, as well as the queries, total number of queries and total time for queries.

## Potential

## Containers
OrientDB has constructs such as containers and links that can be used to manage relations between data and are fairly quick to query. Currently in the PoC only the many_many/belongs_many_many relations use containers (LinkSets) for managing the data. However, this effectively proves the concept that the same approach could be applied to has\_one/belongs\_to/has\_many.

The big advantage of containers is that it removes the necessity for join tables and joins in the queries which can take quite long when the join tables increase in size.

### Inheritance
One area that OrientDB has the potential to improve performance is in it's native handling of class inheritance, this means that class heirarchies such as GrandParent -> Parent -> Child do not need to be represented by multiple tables in the database.

Anecdotally, after implementing support for OrientDB native class inheritance queries for the same operation differed considerably:


