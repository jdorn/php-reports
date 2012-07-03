Php Reports
===========

A light weight, extendable, PHP reporting framework for managing and displaying nice looking, exportable reports from any data source, including SQL and MongoDB

Major features include:

*   Display a report from any data source that can output tabular data (SQL, MongoDB, PHP, etc.)
*   Output reports in HTML, XML, CSV, JSON, or your own custom format
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


Installation Instructions
=================

First, download a pre-packaged zip or tar file or get the latest code from git:

```
git clone git://github.com/jdorn/php-reports.git
cd php-reports
git submodule init
git submodule update
```

Then, set up the config file that defines things like database connections, file paths, etc.

You can use the sample config as a starting point if you want.

```
cp config/config.php.sample config/config.php
```

The config settings are commented and should be pretty self explanatory.

After that, it's time to make some reports!  Detailed report documentation is below.

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


*__note__ Although it is recommended to only use valid JSON, javascript style
declarations are also possible with single quoted values and unquoted or single
quoted strings.

Here's an example SQL report:

```sql
-- This is the report name
-- HEADERNAME: {
--	"param1": "value1"
-- }
-- HEADERNAME2: shortcut, syntax

SELECT * FROM ...
```

VariableHeader
-------------

Used to prompt a user for a value before running a report.

### JSON Format

*   __name__ _required_ The name of the variable.  Should only include alphanumeric characters and underscores.
*   __display__ The display name of the variable.  Can contain any characters.  Defaults to __name__
*   __description__ Will display after the input field in the variable form
*   __type__ The type of variable.  Possible values are:
    *   "text" (the default)
    *   "select" (dropdown list)
    *   "textarea"
    *   "date" (will be parsed with strtotime)
*   __format__ If type is "date", this specifies the date format to convert to.  It defaults to "YYYY-MM-DD HH:MM:SS".
*   __options__ If the type is "select", this should be an array of choices.
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

A few other shortcut formats are supported, but they are not user
friendly and difficult to debug.  Anything more than these basic options
should use JSON.

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

CacheHeader
--------------
The Cache header enables automatic report caching for a specified time.

This is useful for expensive reports where the data doesn't change too often.

### JSON Format

*    __ttl__ The number of seconds to cache the report results for

### Examples

```
CACHE: {"ttl": 3600}
```

This will cache the results for 1 hour (3600 seconds).

### Shortcut Syntax

```
CACHE: 3600

CAHCE: {
	"ttl": 3600
}
```

DetailHeader
------------------
The Detail header is used to tie reports together and provide drill down links.

An example is a report that shows product categories along with the number of products in the category.
Another report shows all the products in a single category.  You could make the Number of Products column
in the 1st report link to the 2nd report.

### JSON Format
*	__column__ _required_ The column that should be turned into a link.  Can either be the column number (starting at 1) or the column name.
*	__report__ _required_ The report to link to.
	*	If the report path starts with "/", it will be relative to the root report directory
	*	Otherwise, it will be relative to the currently running report
*	__macros__ Macros to pass along to the report being linked to.  
	*	Each key in the macros object is the macro name, each value is either a string or 
		{"column": "ColumnName"}.  This 2nd option lets you populate a macro from the row's values.

### Examples
```
DETAIL: {
	"column": "ProductCount",
	"report": "/products/products-in-category.sql",
	"macros": {
		"category": {
			"column": "CategoryName"
		},
		"othermacro": "constant value"
	}
}
```

The ProductCount column will be turned into a link to the products-in-category report.

In each row, the link will contain 2 macros.  
The "category" macro would be equal to the CategoryName column's value in that row.  
The "othermacro" value would always be "constant value".

The products-in-category.sql report would need something like the following to read these macros:

```
VARIABLE: {"name": "category"}
VARIABLE: {"name": "othermacro"}
```

### Shortcut Syntax

```
DETAIL: ProductCount, /products/products-in-category.sql, category=CategoryName, othermacro="constant value"
```

ChartHeader
------------------
The Chart header is used to display graphs and charts of report data.

This uses the Google Visualization API to display line charts, column/bar charts, timelines, map charts, and/or histograms.

The order and datatype of columns required is determined by the Google API for the selected chart type.  For example, line charts require
a string or date column followed by one or more number columns.  Histograms require a single, numeric column.

### JSON Format
*	__columns__ An array of columns to use in the chart.  Columns can be specified by column number (starting at 1) or column name.  Defaults to all columns in order.
*	__type__ The type of chart.  Possible values are:
	*   LineChart (the default)
	*	GeoChart (map)
	*	AnnotatedTimeline (similar to Google Finance)
	*	BarChart
	*	ColumnChart
*	__title__ An optional title for the chart
*	__width__ The width of the chart. Supports any css dimension style (e.g. "400px", "80%", "15em", etc.)
*	__height__ The height of the chart.  Also supports any css dimension style.
*	__xhistogram__ If set to true, a histogram will be constructed.
*	__buckets__ When used with xhistogram, this defines how many buckets to put the data into.

### Examples
```
CHART: {
	"columns": [1,3,4],
	"type": "LineChart",
	"title": "Shopping Cart Abandonment",
	"width": "600px",
	"height": "400px"
}
```
Assume the columns are as follows: "Date", "Revenue", "Number of Completed Carts", "Number of Abandoned Carts"

This will display a line chart with Date on the x axis and Number of Completed Carts and Number of Abandoned Carts on the y axis.

```
CHART: {
	columns: ["Price"],
	'type': "ColumnChart",
	"title": "Product Price",
	"xhistogram": true,
	"buckets": 10
}
```
This will display a histogram of product prices.  Data will be broken up into 10 buckets and displayed as a column chart.

Filters
===================

Filters modify data in a column after running a report, but before displaying it.

All Filter classes extend FilterBase.

Filters are applied with the FILTER header as follows:

```
FILTER: {
	"filter": "geoip",
	"column": 1,
	"params": {}
}
```

For the 1st column in every row, the following will be called.

```
$value = geoipFilter::filter($value, $params);
```

A column can have more than 1 filter and they will be applied in the order they appear in the headers.

htmlFilter
-----------------
This marks a column as containing html data.  Normally all report data is escaped before outputting.  This filter turns off escaping.

This filter has no parameters.

```
FILTER: {
	"filter": "html",
	"column": "My Data"
}
```

If a column contains:

```html
<strong>Data</strong>
```

It would normally be output as:

```html
&lt;strong&gt;Data&lt;/strong&gt;
```

After the htmlFilter is applied, it will output in it's original format and you will be able to see the bold text.

preFilter
-----------------
This wraps a column's contents in pre tags.  It is useful if you want to preserve white space in a column.

This filter has no parameters.

```
FILTER: {
	"filter": "pre",
	"column": 2
}
```

hideFilter
-----------------
This removes a column from a report.  This is useful if you want to use some columns just as a source of data for charts.

This filter has no parameters.

```
FILTER: {
	"filter": "hide",
	"column": 3
}
```

geoipFilter
------------------
This converts ip addresses into human readable locations.  It requires the geoip php extension to work.

This filter has no parameters.

```
FILTER: {
	"filter": "geoip",
	"column": "ip"
}
```

This will convert:
```
173.194.33.3
```
Into:
```
Mountain View, CA
```

classFilter
--------------------
Add a css class to the column.

```
FILTER: {
	"filter": "class",
	"column": 1,
	"params": {
		"class": "right"
	}
}
```

There are a couple of pre-defined css classes that are useful, but feel free to add your own to the template.
*	right (right aligned)
*	center (center justified)

barFilter
---------------------
Turns a numeric column into a horizontal bar chart.  All bars will be scaled to the highest value in the column.

```
FILTER: {
	"filter": "bar",
	"column": 2,
	"params": {
		"width": 300
	}
}
```

The 'width' parameter specified the maximum bar width in pixels.  It defaults to 200px.

linkFilter
----------------------
Turns a column into a link.  The value of the column is used as the href.

```
FILTER: {
	"filter": "link",
	"column": 1,
	"params": {
		"display": "View",
		"blank": true
	}
}
```

This will turn the following:
```
http://localhost/product/view/1/
```
Into:
```
<a href="http://localhost/product/view/1/" target="_blank">View</a>
```

By default, the 'display' parameter is set to the href value.

The 'blank' parameter, if set to true, will open the link in a new window.  The default is false.