-- Name: My Report
-- Description: This is a report.
-- Headers: Id, Start Date, Status, Name
--
-- var START_DATE: {"type": "date","default": "2012-01-01"}
-- var ID: {"type": "int"}
-- var STATUS: {"type": "enum", "options": ["Value1","Value2"], "default": "Value1"}

SELECT Id, DateCreated, Status, Name FROM Testing WHERE DateCreated > "{{START_DATE}}" AND Id = "{{ID}}" AND Status = "{{STATUS}}"
