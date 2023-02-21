# Materialized Path Technique

```sql
CREATE TABLE food (
    id bigint,
    path varchar(255),
    is_group BOOLEAN
);

INSERT INTO food (path, is_group) VALUES ('Food', true);
INSERT INTO food (path, is_group) VALUES ('Food.Fruit', true);
INSERT INTO food (path, is_group) VALUES ('Food.Fruit.Banana', false);
INSERT INTO food (path, is_group) VALUES ('Food.Meat', true);
INSERT INTO food (path, is_group) VALUES ('Food.Meat.Beaf', false);
INSERT INTO food (path, is_group) VALUES ('Food.Meat.Pork', false);

-- All children (Food.Fruit.Banana, Food.Fruit.Cherry):
SELECT * FROM food WHERE path like 'Food.Fruit.%';

-- All parents (Food, Food.Fruit):
SELECT * FROM food WHERE path IN('Food', 'Food.Fruit');

-- Count all food in meat
select count(*) from food where path like 'Food.Meat%' and is_group = false
```

## Add parent_id

-   Add a referential integrity column (e.g., parent_id).
-   Have a trigger upon inserts, which automatically creates your path with the parent path and the current record
-   Do not allow for nodes to exist without parent references.
-   To achieve this, make sure not to allow updates to the path column.
-   Always make sure it is created automatically on inserts.

https://sqlfordevs.com/tree-as-materialized-path
https://dzone.com/articles/materialized-paths-tree-structures-relational-database

# Adjacency List Model

```sql
WITH RECURSIVE tree AS (
    SELECT id, name, 1 AS level, JSON_ARRAY(id) AS path
    FROM employees
    WHERE manager_id = 1
  UNION
    SELECT employees.id, employees.name, tree.level + 1, JSON_ARRAY_APPEND(tree.path, '$', employees.id)
    FROM tree
    JOIN employees ON tree.id = employees.manager_id
    WHERE NOT employees.id MEMBER OF tree.p -- cycle detection
)
SELECT * FROM tree
```

https://sqlfordevs.com/cycle-detection-recursive-query

## Table

```sql
CREATE TABLE category(
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    parent INT DEFAULT NULL
);
```

## Retrieving a Full Tree

```sql
SELECT t1.name AS lev1, t2.name as lev2, t3.name as lev3, t4.name as lev4
FROM category AS t1
LEFT JOIN category AS t2 ON t2.parent = t1.category_id
LEFT JOIN category AS t3 ON t3.parent = t2.category_id
LEFT JOIN category AS t4 ON t4.parent = t3.category_id
WHERE t1.name = 'ELECTRONICS';
```

## Finding all the Leaf Nodes

```sql
SELECT t1.name
FROM category AS t1
LEFT JOIN category as t2
ON t1.category_id = t2.parent
WHERE t2.category_id IS NULL;
```

## Retrieving a Single Path

```sql
SELECT t1.name AS lev1, t2.name as lev2, t3.name as lev3, t4.name as lev4
FROM category AS t1
LEFT JOIN category AS t2 ON t2.parent = t1.category_id
LEFT JOIN category AS t3 ON t3.parent = t2.category_id
LEFT JOIN category AS t4 ON t4.parent = t3.category_id
WHERE t1.name = 'ELECTRONICS' AND t4.name = 'FLASH';
```

## Limitations of the Adjacency List Model

-   We have to know the level at which it resides.
-   Deleting nodes should be careful because of the potential for orphaning an entire sub-tree in the process (delete the portable electronics category and all of its children are orphaned).

# Nested Set Model

## Table

```sql
CREATE TABLE nested_category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    lft INT NOT NULL,
    rgt INT NOT NULL
);
```

## Retrieving a Full Tree

```sql
SELECT node.name
FROM nested_category AS node, nested_category AS parent
WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.name = 'ELECTRONICS'
ORDER BY node.lft;
```

## Finding all the Leaf Nodes

```sql
SELECT name
FROM nested_category
WHERE rgt = lft + 1;
```

## Retrieving a Single Path

```sql
SELECT parent.name
FROM nested_category AS node, nested_category AS parent
WHERE node.lft BETWEEN parent.lft AND parent.rgt AND node.name = 'FLASH'
ORDER BY parent.lft;
```

## Finding the Depth of the Nodes

```sql
SELECT node.name, (COUNT(parent.name) - 1) AS depth
FROM nested_category AS node, nested_category AS parent
WHERE node.lft BETWEEN parent.lft AND parent.rgt
GROUP BY node.name
ORDER BY depth;
```

## Depth of a Sub-Tree

```sql
SELECT node.name, (COUNT(parent.name) - (MAX(sub_tree.depth) + 1)) AS depth
FROM nested_category AS node,
    nested_category AS parent,
    nested_category AS sub_parent,
    (
        SELECT node.name, (COUNT(parent.name) - 1) AS depth
        FROM nested_category AS node, nested_category AS parent
        WHERE node.lft BETWEEN parent.lft AND parent.rgt
            AND node.name = 'PORTABLE ELECTRONICS'
        GROUP BY node.name
        ORDER BY depth
    ) AS sub_tree
WHERE node.lft BETWEEN parent.lft AND parent.rgt
    AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt
    AND sub_parent.name = sub_tree.name
GROUP BY node.name
ORDER BY depth;
```

## Find the Immediate Subordinates of a Node

```sql
SELECT node.name, (COUNT(parent.name) - (MAX(sub_tree.depth) + 1)) AS depth
FROM nested_category AS node,
    nested_category AS parent,
    nested_category AS sub_parent,
    (
        SELECT node.name, (COUNT(parent.name) - 1) AS depth
        FROM nested_category AS node,
            nested_category AS parent
        WHERE node.lft BETWEEN parent.lft AND parent.rgt
            AND node.name = 'PORTABLE ELECTRONICS'
        GROUP BY node.name
        ORDER BY depth
    ) AS sub_tree
WHERE node.lft BETWEEN parent.lft AND parent.rgt
    AND node.lft BETWEEN sub_parent.lft AND sub_parent.rgt
    AND sub_parent.name = sub_tree.name
GROUP BY node.name
HAVING depth <= 1
ORDER BY depth;
```

## Aggregate Functions in a Nested Set

```sql
SELECT parent.name, COUNT(product.name) AS product_count
FROM nested_category AS node,
    nested_category AS parent,
    product
WHERE node.lft BETWEEN parent.lft AND parent.rgt
    AND node.category_id = product.category_id
GROUP BY parent.name
ORDER BY product_count
```

## Adding New Nodes

Add a new node between the TELEVISIONS and PORTABLE ELECTRONICS nodes

```sql
LOCK TABLE nested_category WRITE;

SELECT @myRight := rgt FROM nested_category WHERE name = 'TELEVISIONS';

UPDATE nested_category SET rgt = rgt + 2 WHERE rgt > @myRight;
UPDATE nested_category SET lft = lft + 2 WHERE lft > @myRight;

INSERT INTO nested_category(name, lft, rgt) VALUES('GAME CONSOLES', @myRight + 1, @myRight + 2);

UNLOCK TABLES;
```

Add a node as a child of a node that has no existing children

```sql
LOCK TABLE nested_category WRITE;

SELECT @myLeft := lft FROM nested_category WHERE name = '2 WAY RADIOS';

UPDATE nested_category SET rgt = rgt + 2 WHERE rgt > @myLeft;
UPDATE nested_category SET lft = lft + 2 WHERE lft > @myLeft;

INSERT INTO nested_category(name, lft, rgt) VALUES('FRS', @myLeft + 1, @myLeft + 2);

UNLOCK TABLES;
```

## Deleting a leaf node / a node and all its children

```sql
LOCK TABLE nested_category WRITE;

SELECT @myLeft := lft, @myRight := rgt, @myWidth := rgt - lft + 1
FROM nested_category
WHERE name = 'GAME CONSOLES';

DELETE FROM nested_category WHERE lft BETWEEN @myLeft AND @myRight;

UPDATE nested_category SET rgt = rgt - @myWidth WHERE rgt > @myRight;
UPDATE nested_category SET lft = lft - @myWidth WHERE lft > @myRight;

UNLOCK TABLES;
```

https://mikehillyer.com/articles/managing-hierarchical-data-in-mysql
