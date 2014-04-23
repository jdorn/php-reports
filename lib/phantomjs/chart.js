var page = require('webpage').create();
var args = require('system').args;

var host = args[1];
var report = args[2];
var querystring = args[3];
var width = args[4];
var height = args[5];
var filename = args[6];

var url = host.replace(/\/$/,'')+"/report/chart/?report="+report+"&"+querystring;

page.viewportSize = {width: width, height: height};
page.open(url, function() {
  page.render(filename);
  phantom.exit();
});
