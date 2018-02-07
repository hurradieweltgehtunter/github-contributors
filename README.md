A wordpress plugin to fetch github user activity in an organization

GitHub Events documentation: https://developer.github.com/v3/activity/events/

## ToDo's

- Output: weight user on oc index
- add stats for user (activiy, ...); create separate table for this
- handle non-existing users in activity fetching 
- provide a clean plugin submenu in backend
- make sure all dependencies are installed correctly
- forward to settings page after install
- add handlers for github api errors (current action gets stuck)
- Add action to handle uncatched github events
- prevent automatic feed search if meta field "feed" is not empty (if manually entered)
- Setting a post status to "trashed" from "draft" crashes the URL; Must be set to "publish", then to "trash"

- create the output :white_check_mark:
- remove users who left the organziation :white_check_mark:
- remove .DS_store :white_check_mark:
- remove file_put_contents log messages / move to log table :white_check_mark:
- check githubsecrets/company etc and block plugin if not entered :white_check_mark:
- add uninstall routine :white_check_mark:
- remove all custom post type posts on uninstall :white_check_mark: