Php Reports
===========

A light weight, extendable, PHP reporting framework for managing and displaying nice looking, exportable reports from any data source, including SQL and MongoDB

Major features include:

*   Display a report from any data source that can output tabular data (SQL, MongoDB, PHP, etc.)
*   Output reports in HTML, XML, CSV, JSON, or pretty much any other format
*   Add customizable parameters to a report (e.g. start date and end date)
*   Support for graphs and charts with the Google Data Visualization API
*   Easily switch between database environments (e.g. Production, Staging, and Dev)
*   Fully extendable and customizable

Introduction
============

Reports are organized and grouped in directories.  Each report is it's own file.

A report consists of headers containing meta-data (e.g. name, description, column formatting, etc.) 
and the actual report (SQL statements, MongoDB js file, PHP code, etc.).

All reports return rows of data which are then outputted in the specified format (HTML, CSV, etc.).

The Php Reports framework ties together all these different report types, output formats, and meta-data into
a consistent interface.

Example Reports
==============

Here's an example of a SQL report:

```sql
-- My Report
-- This lists all the products that cost
-- more than a given price.
-- VARIABLE: min_price, Minimum Price

SELECT Name, Price FROM Products WHERE Price > "{{min_price}}"
```

The set of SQL comments at the top are the report headers.  

The first row is always the report name.  After that comes a description.  Following that are any parameters or options.

The VARIABLE header tells the report framework to prompt the user before running the report.  Once a value is provided,
it will be passed into the report body ("{{min_price}}" in this example).


Here's a MongoDB report:

```js
// My Other Report
// Lists all food products in a MongoDB collection
// MONGODATABASE: MyDatabase
// VARIABLE: include_inactive, Include Inactive?, yes|no

var query = {'type': 'food'};

if(include_inactive == 'no') {
    query.status = 'active';
}

var result = db.Products.find(query);

printjson(result);
```

As you can see, the structure is very similar.  MongoDB reports use javascript style comments for the headers, but everything else remains the same.

The MONGODATABASE header, if specified, will populate the 'db' variable.

All VARIABLE headers are defined as variables in javascript before the report is run.


Here's a PHP Report:

```php
<?php
//My Third Report
//This connects to the Stripe Payments api and shows a list of charges
//INCLUDE: /stripe.php
//VARIABLE: count, Number of Charges

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

Hopefully, you can begin to see the power of the Php Report Framework.  

Now, we'll dig into the various report headers available, the different output formats, the configuration options, 
and how to extend the Php Reports Framework to make it your own.


Detailed Documentation
=================

This will cover all the pre-defined report headers, filters, etc..  
It will also give instructions for how to extend and customize the framework to add your own.

Report Headers
================

All pre-defined header classes are located in classes/headers/ and extend HeaderBase.

Headers appear as comments at the top of a report file.

All headers support the following JSON format:

```
HEADERNAME: {
    "param1": "value1",
    "param2": { 
        "nested": true 
    }
}
```

Many headers also have a shortcut syntax for one or more common use cases.


VariableHeader
-------------

Used to prompt a user for a value before running a report.

### JSON Format

*   __name__ _required_ The name of the variable.  Should only include alphanumeric characters and underscores.
*   __display__ The display name of the variable.  Can contain any characters.  Defaults to __name__
*   __type__ The type of variable.  Possible values are:
    *   "text" (the default)
    *   "select" (dropdown list)
    *   "textarea"
    *   "date" (will be parsed with strtotime and turned into "YYYY-MM-DD HH:MM:SS" format)
*   __options__ If the variable type is "select", this should be an array of choices.
*   __default__ The default value for the variable.
*   __empty__ If set to true, the report will run even if the value is empty.
*   __multiple__ If set to true, multiple values can be chosen.
    *   If type is "select", it will be a multiselect box.
    *   If type is "textarea", the value will be split by line breaks.
*   __database_options__ If type is "select", this tells the framework to populate the drop down list from a database table.  It is an object with the following properties:
    *   __table__ The database table to select from
    *   __column__ The column to select
    *   __where__ An optional WHERE clause
    *   __all__ If set to true, an additional option named "ALL" will be added to the top of the list.

### Examples

```
VARIABLE: {"name": "date_start", "display": "Start Date", "type": "date"}
```

```
VARIABLE: {
    "name": "type",
    "default": "food"
}
```

```
VARIABLE: {
    "name": "category",
    "type": "select",
    "database_options": {
        "table": "categories",
        "column": "CategoryName",
        "where": "CategoryStatus = 'active'"
    }
}
```

### Shortcut syntax

Basic "text" variable

```
VARIABLE: varname, Display Name

VARIABLE: {
    "name": "varname",
    "display": "Display Name",
    "type": "text"
}
```

A "select" type variable with defined options

```
VARIABLE: varname, Display Name, option 1|option 2|option 3

VARIABLE: {
    "name": "varname",
    "display": "Display Name",
    "type": "select",
    "options": ["option 1", "option 2", "option 3"]
}
```

IncludeHeader
---------------
Includes another report in the currently running one.

The included report's headers are parsed and the report contents are prepended to the current report before running.

Possible uses include:
*   Creating a temp table for a set of MySQL reports
*   Defining helper functions for MongoDB or PHP reports
*   Setting up an API connection for a PHP report

### JSON Format

*   __report__ _required_ The path to a report to include.  If it starts with "/", it will be relative to the root report directory.  Otherwise, it will be relative to the currently running report.

### Examples

```
INCLUDE: {"report": "relative/to/current/report.sql"}
```

```
INCLUDE: {"report": "/relative/to/reportdir/report.sql";
```

### Shortcut Syntax

```
INCLUDE: relative/path/to/report.sql
INCLUDE: /path/to/report.sql
```

FilterHeader
--------------
The Filter header applies a filter to a column of the report.

Possible uses include:
*   GeoIP lookup that replaces an IP address with City, State Country
*   HTML filter that preserves html in that column (row values are escaped by default)
*   Star Rating filter that replaces a number (1-10) with that many images of stars.
*   Add a CSS class to a column

Each pre-defined filter has it's own documentation.  This is just for the FILTER header itself.

### JSON Format

*   __column__ _required_ The column to apply the filter to.  Can be the column number (starting at 1) or the column name.
*   __filter__ _required_ The filter to apply to the column.  Must be a valid filter class.  "geoip" maps to "geoipFilter".
*   __params__ An object containing parameters to pass into the filter class.  Each filter class accepts different parameters.

### Examples

```
FILTER: {"column": 1, "filter": "geoip"}
```

```
FILTER: {
    "column": "Article", 
    "filter": "link",
    "params": {
        "_blank": true,
        "display": "View Article"
    }
}
```

### Shortcut Syntax

```
FILTER: 1, geoip

FILTER: {
    "column": 1,
    "filter": "geoip"
}
```


