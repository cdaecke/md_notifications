# TYPO3 Extension `md_notifications`
This extension adds notifications to configured record types for frontend users.
In a list, a frontend user can see, whether or not an item was read by
himself. Additional it is possible to show the number of notifications for each
record type or for all record types together.

## Requirements

- TYPO3 v12.4 || v13.4

## Screenshots

![Screenshot list](./Documentation/Images/list_view.png?raw=true "List view")

## Installation
- Install the extension by using composer (`composer req mediadreams/md-notifications`) or use the extension manager
- Include the static TypoScript of the extension
- Configure the extension (see chapter [Configuration](#configuration))

## Configuration
Configuration is done in the sites configuration file. Either add the configuration
directly in `config/sites/{site-itentifier}/config.yaml`, or import a YAML file
in your site configuration by using this command:

```
imports:
    - { resource: "EXT:my_extension/Configuration/Yaml/MdNotifications.yaml" }
```

Following configuration can be added:

```
md_notifications:
    # The page Id, where the notification records get stored
    # If no `storagePid` is provided, the records will be saved on page Id = 0
    storagePid: 123

    # If this is set, notifications will be saved for users who belong to groupId only.
    feGroup: 3

    # Configure recod types for notifications
    # All record types can be configured. Just add a section for the table name of the record.

    # All pages which have page Id 6 in it's rootline, will get notifications.
    pages:
        - 6

    # All news records which will be saved on a pages within the rootline of page 2 or 16 will receive notofications.
    tx_news_domain_model_news:
        - 2
        - 16
```

Hint:<br>
You can use environment variables in the site configuration like this:

    storagePid: "%env(md_notifications_storagePid)%"

## Usage
As soon as you have installed and activated the extension, it will hook into
the saving process of records. Everytime a backend user adds a
configured record, the unread info for this record and the configured
`feUsers` will be added.

### List plugin
The extension ships a content element `Notifications`, which shows a list of all
notifications for the logged in user. In the `Plugin`-tab you have the following
configuration options:

- `Record keys`<br>Comma separated list of record keys (table names). Leave empty, if all records shall be shown.<br>Example: `pages, tx_news_domain_model_news`
- `Startingpoint`<br>The page on which the notification records are stored.

### Notification counter
This counter will show the number of notifications for a user.

Use following code in your fluid template in order to get number of all notifications:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsCount" />

Use this to get number of notifications for example for `pages`
and `tx_news_domain_model_news` records:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsCount" data="{recordKeys:'pages,tx_news_domain_model_news'}" />

### Show notification info of record
Use the following code in the fluid template to show, whether the current
logged in feUser has read the item.

Example for a `page`-record:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsHasSeen" data="{recordKey:'pages', recordUid:'{data.uid}'}" />

Example for a `news`-record:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsHasSeen" data="{recordKey:'tx_news_domain_model_news', recordUid:'{newsItem.uid}'}" />

### Remove notification info
Remove the notification info as soon, as the user has read the item. Use
the following code in your fluid template.

Example for a `page`-record:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsRemove" data="{recordKey:'pages', recordUid:'{data.uid}'}" />

Example for a `news`-record:

    <f:cObject typoscriptObjectPath="lib.mdNotificationsRemove" data="{recordKey:'tx_news_domain_model_news', recordUid:'{newsItem.uid}'}" />

### Send reminder emails
You can send reminder emails about notifications, which are not seen yet.
Therefor you will find a scheduler task called `mdNotifications:reminder`.

Setup scheduler task:

* Go to `Scheduler`
* Click `New task`
* Select `Execute console commands` in the `Task`-dropdown
* Select `mdNotifications:reminder` in the dropdown `Schedulable Command`
* Add a value in the field `Frequency`
* Press the `Save` button
* Add value for `storages`. This is a comma separated list of IDs where the
  notification data is stored.
* Add value for `listPageUid`. This is the Uid of the page, which holds the list
  of notification items. This page will be linked in the email.
* Add value for `mailSubject`. The is the subject of the email, which will be sent.
* Add optional value `mailTemplate`. With this option, your are able to set
  a templates for the task. Enter the name of the HTML file, which shall be
  used for the e-mail.
* Press the Save button again

Hint:<br>
You can setup individual tasks for individual notification types. Therefor
add more than one `mdNotifications:reminder`-tasks and configure individually.

#### E-Mail template
In order to change the E-Mail template, add the path to your site extension in
global configuration in the section `[MAIL][templateRootPaths]`. As soon, as you have added
the path to your extension, you can copy the `Notifications.html` template from
`Resources/Private/Templates/Email/` to your path and do your modifications.

**Important:** This extension registers its template root path with the array index `2025`.
TYPO3 resolves `templateRootPaths` from the highest index to the lowest, so your custom
path must be registered with an index **higher than `2025`** to take precedence.

Example in your `ext_localconf.php`:

```php
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][2100] = 'EXT:my_sitepackage/Resources/Private/Templates/Email/';
```

Place your customized template at:

```
EXT:my_sitepackage/Resources/Private/Templates/Email/Notifications.html
```

### Remove items
If you wish to remove unread items, which are older than a certain time, you
can use the following scheduler task:

* Go to `Scheduler`
* Click `New task`
* Select `Table garbage collection` in the `Task`-dropdown
* Select `tx_mdnotifications_domain_model_notification` from `Table to clean up`-dropdown
* Set a value in field `Delete entries older than given number of days`
* Add a value in the field `Frequency`
* Press the `Save` button

## Bugs and Known Issues
If you find a bug, it would be nice if you add an issue on
[Github](https://github.com/cdaecke/md_notifications/issues).

# THANKS
Thanks a lot to all who make this outstanding TYPO3 project possible!

The TYPO3 project - inspiring people to share!

## Credits
- Extension icon was kindly taken from [Font Awesome](https://fontawesome.com/icons/bell?f=classic&s=solid).
