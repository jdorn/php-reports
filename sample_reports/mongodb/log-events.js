// Last 50 Log Events
// OPTIONS: { mongodatabase: "Logs" }

var result = db.logs.find({}).limit(50).sort({date: -1});

// Build report rows
var rows = [];
result.forEach(function(el) {
    rows.push({
        'Date': el.date,
        'Description': el.description
    });
});

// Print out the rows
printjson(rows);
