String.prototype.QueryStringToJSON = function () {
href = this;
qStr = href.replace(/(.*?\?)/, '');
qArr = qStr.split('&');
stack = {};
for (var i in qArr) {
    var a = qArr[i].split('=');
    var name = a[0],
        value = isNaN(a[1]) ? a[1] : parseFloat(a[1]);
    if (name.match(/(.*?)\[(.*?)]/)) {
        name = RegExp.$1;
        name2 = RegExp.$2;
        //alert(RegExp.$2)
        if (name2) {
            if (!(name in stack)) {
                stack[name] = {};
            }
            stack[name][name2] = value;
        } else {
            if (!(name in stack)) {
                stack[name] = [];
            }
            stack[name].push(value);
        }
    } else {
        stack[name] = value;
    }
}
return stack;
}
