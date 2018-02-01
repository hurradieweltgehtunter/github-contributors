A wordpress plugin to fetch github user activity in an organization

GitHub Events documentation: https://developer.github.com/v3/activity/events/

## ToDo's
- create the output :white_check_mark:
- Output: weight user on oc index
- add stats for user (activiy, ...); create separate table for this
- make a clean uninstall process
- handle non-existing users in activity fetching 
- remove users who left the organziation :white_check_mark:
- provide a clean plugin submenu in backend
- remove .DS_store :white_check_mark:
- make sure all dependencies are installed correctly
- remove file_put_contents log messages / move to log table :white_check_mark:
- check githubsecrets/company etc and block plugin if not entered
- add handlers for github api errors (current action gets stuck)
