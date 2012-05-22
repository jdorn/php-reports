-- Employees by Job Title
-- VARIABLE: job_title, Job Title, employees.jobTitle, ALL

SELECT * FROM employees {{^job_title_all}}WHERE jobTitle="{{job_title}}"{{/job_title_all}}
