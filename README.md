Php Reports
===========

A reporting framework for managing and displaying nice looking, exportable reports from any data source, including SQL and MongoDB.

Major features include:

*   Display a report from any data source that can output tabular data (SQL, MongoDB, PHP, etc.)
*   Output reports in HTML, XML, CSV, JSON, or your own custom format
*   Add customizable parameters to a report (e.g. start date and end date)
*   Add graphs and charts with the Google Data Visualization API
*   Supports multiple database environments (e.g. Production, Staging, and Dev)
*   Fully extendable and customizable

For installation instructions and documentation, check out http://jdorn.github.io/php-reports/

If you have a question, post on the official forum - http://ost.io/@jdorn/php-reports


Basic Introduction
============

Reports are organized and grouped in directories.  Each report is it's own file.

A report consists of headers containing meta-data (e.g. name and description) 
and the actual report (SQL queries, javascript, or PHP code).

All reports return rows of data which are then displayed in a sortable/searchable HTML table.

Reports can be exported to a number of formats including CSV, XLS, JSON, and XML.

The Php Reports framework ties together all these different report types, output formats, and meta-data into
a consistent interface.

Example Reports
==============

Here's an example SQL report:

```sql
-- Products That Cost At Least $X
-- VARIABLE: {"name": "min_price"}

SELECT Name, Price FROM Products WHERE Price > "{{min_price}}"
```

The set of SQL comments at the top are the report headers.  The first row is always the report name.

The `VARIABLE` header tells the report framework to prompt the user for a value before running the report.  Once provided
it will be passed into the report body ("{{min_price}}" in this example) and executed.


Here's a MongoDB report:

```js
// List of All Foods
// MONGODATABASE: MyDatabase
// VARIABLE: {
//   "name": "include_inactive", 
//   "display": "Include Inactive?", 
//   "type": "select",
//   "options": ["yes","no"]
// }

var query = {'type': 'food'};

if(include_inactive == 'no') {
    query.status = 'active';
}

var result = db.Products.find(query);

printjson(result);
```

As you can see, the structure is very similar.  MongoDB reports use javascript style comments for the headers, but everything else remains the same.

The MONGODATABASE header, if specified, will populate the 'db' variable.


Here's a PHP Report:

```php
<?php
//List of Payment Charges
//This connects to the Stripe Payments api and shows a list of charges
//INCLUDE: /stripe.php
//VARIABLE: {"name": "count", "display": "Number to Display"}

if($count > 100 || $count < 1) throw new Exception("Count must be between 1 and 100");

$charges = Stripe_Charge::all(array("count" => $count));

$rows = array();
foreach($charges as $charge) {
    $rows[] = array(
        'Charge Id'=>$charge->id,
        'Amount'=>number_format($charge->amount/100,2),
        'Date'=>date('Y-m-d',$charge->created)
    );
}

echo json_encode($rows);
?>
```
Again, the header format is very similar.  

The INCLUDE header includes another report within the running one.  Below is example content of /stripe.php:

```php
<?php
//Stripe PHP Included Report
//You can have headers here too; even nested INCLUDE headers!
//Some headers will even bubble up to the parent, such as the VARIABLE header

//include the Stripe API client
require_once('lib/Stripe/Stripe.php');

//set the Stripe api key
Stripe::setApiKey("123456");
?>
```

Hopefully, you can begin to see the power of Php Reports.

For full documentation and information on getting started, check out http://jdorn.github.io/php-reports/
