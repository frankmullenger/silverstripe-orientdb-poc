# Performance

## Inheritance
One area that OrientDB has the potential to improve performance is in it's native handling of class inheritance, this means that class heirarchies such as GrandParent -> Parent -> Child do not need to be represented by multiple tables in the database.

Anecdotally, after implementing support for OrientDB native class inheritance queries for the same operation differed considerably:

### MySQL

```
INSERT INTO "Family_GreatGrandParent" ("Created") VALUES (NOW()) 0.0006ms

SELECT "ID" FROM "Family_GreatGrandParent" WHERE "ID" = 9 0.0003ms

UPDATE "Family_GreatGrandParent" SET "ClassName" = 'Family_Child', "GreatGrandParent" = '1', "Name" = 'Name 254', "Title" = 'Child', "LastEdited" = '2013-10-11 16:49:28', "Created" = '2013-10-11 16:49:28' where "ID" = 9 0.0006ms

INSERT INTO "Family_GrandParent" ("ID", "GrandParent") VALUES (9, '1') 0.0004ms

INSERT INTO "Family_Parent" ("ID", "Parent") VALUES (9, '1') 0.0003ms

INSERT INTO "Family_Child" ("ID", "Child") VALUES (9, '1') 0.0004ms

SHOW TABLES LIKE 'Family_GreatGrandParent' 0.0004ms

SHOW FULL FIELDS IN "Family_GreatGrandParent" 0.0011ms

SELECT DISTINCT "ClassName" FROM "Family_GreatGrandParent" 0.0008ms

SELECT DISTINCT "Family_GreatGrandParent"."ClassName", "Family_GreatGrandParent"."Created", "Family_GreatGrandParent"."LastEdited", "Family_GreatGrandParent"."Name", "Family_GreatGrandParent"."Title", "Family_GreatGrandParent"."GreatGrandParent", "Family_GreatGrandParent"."NewFieldOne", "Family_GrandParent"."GrandParent", "Family_Parent"."Parent", "Family_Child"."Child", "Family_GreatGrandParent"."ID", CASE WHEN "Family_GreatGrandParent"."ClassName" IS NOT NULL THEN "Family_GreatGrandParent"."ClassName" ELSE 'Family_GreatGrandParent' END AS "RecordClassName" FROM "Family_GreatGrandParent" LEFT JOIN "Family_GrandParent" ON "Family_GrandParent"."ID" = "Family_GreatGrandParent"."ID" LEFT JOIN "Family_Parent" ON "Family_Parent"."ID" = "Family_GreatGrandParent"."ID" LEFT JOIN "Family_Child" ON "Family_Child"."ID" = "Family_GreatGrandParent"."ID" WHERE ("Family_GreatGrandParent"."ClassName" IN ('Family_Child')) LIMIT 1 0.0006ms 
```

0.0006
0.0003
0.0006
0.0004
0.0003
0.0004
0.0004
0.0011
0.0008
0.0006
__Total:__ 0.0055ms

### OrientDB

```
INSERT INTO Family_Child (Created) VALUES (sysdate('yyyy-MM-dd HH:mm:ss')) 0.001ms

SELECT * FROM Family_Child WHERE @rid = #33:10 0.0007ms

UPDATE Family_Child SET Child = '1', LastEdited = '2013-10-11 17:04:45', Created = '2013-10-11 17:04:45', ClassName = 'Family_Child', ID = '33:10' WHERE @rid = #33:10 0.0008ms

SELECT * FROM Family_Child LIMIT 1 0.0006ms 
```

0.001
0.0007
0.0008
0.0006
__Total:__ 0.0031ms

### Results

Although this is far from a suitable benchmark the result in this case was very positive:  
0.0031ms / 0.0055ms = 56% therefore roughly 44% increase  
10 queries down to 4


