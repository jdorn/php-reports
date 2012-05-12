-- Name: My Report
-- Description: This is a report.
--
-- Variable: start_date, {"type": "date","default": "2012-01-01"}
-- Variable: id, {"type": "int"}
-- Variable: status, {"type": "select", "options": ["Value1","Value2"], "default": "Value1"}

SELECT Id, DateCreated, Status, Name FROM Testing WHERE DateCreated > "{{start_date}}" AND Id = "{{id}}" AND Status = "{{status}}"
