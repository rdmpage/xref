# xref
Graph database to crosslink biodiversity data

## Ideas

Need three major components, a crawler, a document store to hold results of crawling, and a graph database to model relationships.

## Crawler

```
while (queue not empty)
{
	dequeue url_doc
	fetch content from url

	add links to queue
}
```

### Adding to queue
An item added to the queue contains the URL to fetch the data, a timestamp (used to order the queue), and an empty content field.

### Fetching content
Each item is identified by the URL that we use to fetch content.

### Queue
If document has empty content, place it in queue ordered by timestamp.

If document has content, we use one or more views to create derived views, such as CYPHER commands for Neo4j


## Graph database

Use Neo4J to manage graph relationships. CYPHER queries can be generated from the documents stored in CouchDB. 

### CYPHER
Designing CYPHER queries to create data is something of a challenge. We need to create nodes before relationships, and we need to avoid creating more than one node per object.

### Design issues

#### Handling multiple identifiers

http://portal.graphgist.org/graph_gists/0ef6f614-f3e5-4b3f-8485-54d24741609d

#### Dates as time trees

See http://jexp.de/blog/2014/04/importing-forests-into-neo4j/

MERGE (y:Year {id:2015})
MERGE (y)<-[:PART_OF]-(m:Month {id:7})
MERGE (article)-[:PUBLISHED]->(m)
RETURN y, m, article

Find number of articles published in 2015

MATCH (y:Year {id:2015})<-[:PART_OF*0..2]-(n)<-[:PUBLISHED]-(article)
RETURN y.id, count(article)



## Neo4J queries

### Managing the database

#### Clean up

```
MATCH (n)
OPTIONAL MATCH (n)-[r]-()
DELETE n,r
```

If graph is very big then delete databas eitself from command line

```
rm -r data/graph.db
```

#### Show all schema

```
:schema
```
#### Show everything in the graph

```
MATCH (n) RETURN n
```

### Constraints

We can enforce constraints such as ensuring on one node for each identifier

```
CREATE CONSTRAINT ON (i:identifier) ASSERT i.id IS UNIQUE
```

We can also delete a constraint

```
DROP CONSTRAINT ON (i:identifier) ASSERT i.id IS UNIQUE
```
