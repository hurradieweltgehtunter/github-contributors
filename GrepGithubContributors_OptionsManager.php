<?php
/*
    "WordPress Plugin Template" Copyright (C) 2018 Michael Simpson  (email : michael.d.simpson@gmail.com)

    This file is part of WordPress Plugin Template for WordPress.

    WordPress Plugin Template is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WordPress Plugin Template is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Contact Form to Database Extension.
    If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/

class GrepGithubContributors_OptionsManager {
    private $errors = array();

    public function getOptionNamePrefix() {
        return get_class($this) . '_';
    }


    /**
     * Define your options meta data here as an array, where each element in the array
     * @return array of key=>display-name and/or key=>array(display-name, choice1, choice2, ...)
     * key: an option name for the key (this name will be given a prefix when stored in
     * the database to ensure it does not conflict with other plugin options)
     * value: can be one of two things:
     *   (1) string display name for displaying the name of the option to the user on a web page
     *   (2) array where the first element is a display name (as above) and the rest of
     *       the elements are choices of values that the user can select
     * e.g.
     * array(
     *   'item' => 'Item:',             // key => display-name
     *   'rating' => array(             // key => array ( display-name, choice1, choice2, ...)
     *       'CanDoOperationX' => array('Can do Operation X', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber'),
     *       'Rating:', 'Excellent', 'Good', 'Fair', 'Poor')
     */
    public function getOptionMetaData() {
        return array();
    }

    /**
     * @return array of string name of options
     */
    public function getOptionNames() {
        return array_keys($this->getOptionMetaData());
    }

    /**
     * Override this method to initialize options to default values and save to the database with add_option
     * @return void
     */
    protected function initOptions() {
    }

    /**
     * Cleanup: remove all options from the DB
     * @return void
     */
    protected function deleteSavedOptions() {
        $optionMetaData = $this->getOptionMetaData();
        if (is_array($optionMetaData)) {
            foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                $prefixedOptionName = $this->prefix($aOptionKey); // how it is stored in DB
                delete_option($prefixedOptionName);
            }
        }
    }

    /**
     * @return string display name of the plugin to show as a name/title in HTML.
     * Just returns the class name. Override this method to return something more readable
     */
    public function getPluginDisplayName() {
        return get_class($this);
    }

    /**
     * Get the prefixed version input $name suitable for storing in WP options
     * Idempotent: if $optionName is already prefixed, it is not prefixed again, it is returned without change
     * @param  $name string option name to prefix. Defined in settings.php and set as keys of $this->optionMetaData
     * @return string
     */
    public function prefix($name) {
        $optionNamePrefix = $this->getOptionNamePrefix();
        if (strpos($name, $optionNamePrefix) === 0) { // 0 but not false
            return $name; // already prefixed
        }
        return $optionNamePrefix . $name;
    }

    /**
     * Remove the prefix from the input $name.
     * Idempotent: If no prefix found, just returns what was input.
     * @param  $name string
     * @return string $optionName without the prefix.
     */
    public function &unPrefix($name) {
        $optionNamePrefix = $this->getOptionNamePrefix();
        if (strpos($name, $optionNamePrefix) === 0) {
            return substr($name, strlen($optionNamePrefix));
        }
        return $name;
    }

    /**
     * A wrapper function delegating to WP get_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param $default string default value to return if the option is not set
     * @return string the value from delegated call to get_option(), or optional default value
     * if option is not set.
     */
    public function getOption($optionName, $default = null) {
        $prefixedOptionName = $this->prefix($optionName); // how it is stored in DB
        $retVal = get_option($prefixedOptionName);
        if (!$retVal && $default) {
            $retVal = $default;
        }
        return $retVal;
    }

    /**
     * A wrapper function delegating to WP delete_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @return bool from delegated call to delete_option()
     */
    public function deleteOption($optionName) {
        $prefixedOptionName = $this->prefix($optionName); // how it is stored in DB
        return delete_option($prefixedOptionName);
    }

    /**
     * A wrapper function delegating to WP add_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param  $value mixed the new value
     * @return null from delegated call to delete_option()
     */
    public function addOption($optionName, $value) {
        $prefixedOptionName = $this->prefix($optionName); // how it is stored in DB
        return add_option($prefixedOptionName, $value);
    }

    /**
     * A wrapper function delegating to WP add_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param  $value mixed the new value
     * @return null from delegated call to delete_option()
     */
    public function updateOption($optionName, $value) {
        $prefixedOptionName = $this->prefix($optionName); // how it is stored in DB
        return update_option($prefixedOptionName, $value);
    }

    /**
     * A Role Option is an option defined in getOptionMetaData() as a choice of WP standard roles, e.g.
     * 'CanDoOperationX' => array('Can do Operation X', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber')
     * The idea is use an option to indicate what role level a user must minimally have in order to do some operation.
     * So if a Role Option 'CanDoOperationX' is set to 'Editor' then users which role 'Editor' or above should be
     * able to do Operation X.
     * Also see: canUserDoRoleOption()
     * @param  $optionName
     * @return string role name
     */
    public function getRoleOption($optionName) {
        $roleAllowed = $this->getOption($optionName);
        if (!$roleAllowed || $roleAllowed == '') {
            $roleAllowed = 'Administrator';
        }
        return $roleAllowed;
    }

    /**
     * Given a WP role name, return a WP capability which only that role and roles above it have
     * http://codex.wordpress.org/Roles_and_Capabilities
     * @param  $roleName
     * @return string a WP capability or '' if unknown input role
     */
    protected function roleToCapability($roleName) {
        switch ($roleName) {
            case 'Super Admin':
                return 'manage_options';
            case 'Administrator':
                return 'manage_options';
            case 'Editor':
                return 'publish_pages';
            case 'Author':
                return 'publish_posts';
            case 'Contributor':
                return 'edit_posts';
            case 'Subscriber':
                return 'read';
            case 'Anyone':
                return 'read';
        }
        return '';
    }

    /**
     * @param $roleName string a standard WP role name like 'Administrator'
     * @return bool
     */
    public function isUserRoleEqualOrBetterThan($roleName) {
        if ('Anyone' == $roleName) {
            return true;
        }
        $capability = $this->roleToCapability($roleName);
        return current_user_can($capability);
    }

    /**
     * @param  $optionName string name of a Role option (see comments in getRoleOption())
     * @return bool indicates if the user has adequate permissions
     */
    public function canUserDoRoleOption($optionName) {
        $roleAllowed = $this->getRoleOption($optionName);
        if ('Anyone' == $roleAllowed) {
            return true;
        }
        return $this->isUserRoleEqualOrBetterThan($roleAllowed);
    }

    /**
     * see: http://codex.wordpress.org/Creating_Options_Pages
     * @return void
     */
    public function createSettingsMenu() {
        $pluginName = $this->getPluginDisplayName();
        //create new top-level menu
        add_menu_page($pluginName . ' Plugin Settings',
                      $pluginName,
                      'administrator',
                      get_class($this),
                      array(&$this, 'settingsPage')
        /*,plugins_url('/images/icon.png', __FILE__)*/); // if you call 'plugins_url; be sure to "require_once" it

        //call register settings function
        add_action('admin_init', array(&$this, 'registerSettings'));
    }

    public function registerSettings() {
        $settingsGroup = get_class($this) . '-settings-group';
        $optionMetaData = $this->getOptionMetaData();
        foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
            register_setting($settingsGroup, $aOptionMeta);
        }
    }

    /**
     * Creates HTML for the Administration page to set options for this plugin.
     * Override this method to create a customized page.
     * @return void
     */
    public function settingsPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'grep-github-contributors'));
        }

        $optionMetaData = $this->getOptionMetaData();

        // Save Posted Options
        if ($optionMetaData != null) {
            foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                if (isset($_POST[$aOptionKey])) {
                    $this->updateOption($aOptionKey, $_POST[$aOptionKey]);
                }
            }
        }

        // check required settings
        $start  = array('done' => true, 'errors' => array());

        if ($this->getOption('github-client-id') === '') {
            $start['done'] = false;
            $this->errors[] = 'github-client-id';
        }

        if ($this->getOption('github-client-id') === '') {
            $start['done'] = false;
            $this->errors[] = 'github-client-secret';
        }

        if ($this->getOption('github-organization') === '') {
            $start['done'] = false;
            $this->errors[] = 'github-organization';
        }

        try {
            $members = $this->client->api('organizations')->members()->all($this->getOption('github-organization'));  
        } catch (Exception $e) {
            $start['done'] = false;
            $this->errors[] = 'github-organization';
        }

        if(isset($_POST['start-plugin'])) {
            if (count($this->errors) === 0) {
                if( wp_next_scheduled( 'grep-github-contributors-get-members' ) ) {  
                    wp_clear_scheduled_hook( 'grep-github-contributors-get-members' );
                }

                wp_schedule_event( time() - 1, 'daily', 'grep-github-contributors-get-members' );
                spawn_cron();    
            }
        }

        // HTML for the page
        $settingsGroup = get_class($this) . '-settings-group';
        ?>
        <div class="wrap">
            <h2><?php echo _e('Settings', 'grep-github-contributors'); ?></h2>

            <?php
            if (count($this->errors) === 0) : ?>
                <?php
                if (false === wp_next_scheduled( 'grep-github-contributors-get-members' )) : ?>
                    <form method="post" action="">
                        <p>Everything is set up. Hit "Start" to begin. The plugin will then work independently.</p>

                        <input type="hidden" name="start-plugin" value="1"/>
                        <input type="submit" class="button-primary" value="<?php _e('Start', 'grep-github-contributors'); ?>"/>
                    </form>
                <?php
                else :
                ?>
                    <b>Next scheduled events:</b>
                    <div>
                        Fetch organization members from github: 
                        <?php
                        $schedule = wp_next_scheduled( 'grep-github-contributors-get-members' );

                        if ($schedule > 0) {
                            if ($schedule - time() <= 0) 
                                $timeleft = 'now';
                            else {
                                $timeleft = gmdate('H:i:s', $schedule - time()) . ' left, running ' . wp_get_schedule('grep-github-contributors-get-members' );
                            }

                            echo date('d.m.Y H:i', $schedule) . ' (' . $timeleft . ')';    
                        } else {
                            echo 'not set';
                        }
                        
                        ?>
                    </div>

                    <div>
                        Fetch members GitHub activities: 
                        <?php
                        $schedule = wp_next_scheduled( 'grep-github-contributors-get-member-activity' );
                        
                        if ($schedule > 0) {
                            if ($schedule - time() <= 0) 
                                $timeleft = 'now';
                            else {
                                $timeleft = gmdate('H:i:s', $schedule - time()) . ' left';    
                            }

                            echo date('d.m.Y H:i', $schedule) . ' (' . $timeleft . ')';    
                        } else {
                            echo 'not set';
                        }
                        
                        ?>
                    </div>

                    <div>
                        Fetch blogposts of github members blogs:
                        <?php
                        $schedule = wp_next_scheduled( 'grep-github-contributors-get-member-feed' );
                        
                        if ($schedule > 0) {
                            if ($schedule - time() <= 0) 
                                $timeleft = 'now';
                            else {
                                $timeleft = gmdate('H:i:s', $schedule - time()) . ' left';
                            }

                            echo date('d.m.Y H:i', $schedule) . ' (' . $timeleft . ')';    
                        } else {
                            echo 'not set';
                        }
                        
                        ?>
                    </div>
                <?php
                endif;
                ?>
                <br />
                <br />
                <?php
            else :
            wp_clear_scheduled_hook( 'grep-github-contributors-get-members' ); ?>

            <?php
            endif;
            ?>

            <form method="post" action="">
            <?php settings_fields($settingsGroup); ?>
                <style type="text/css">
                    table.plugin-options-table {width: 100%; padding: 0;}
                    table.plugin-options-table tr:nth-child(even) {background: #f9f9f9}
                    table.plugin-options-table tr:nth-child(odd) {background: #FFF}
                    table.plugin-options-table tr:first-child {width: 35%;}
                    table.plugin-options-table td {vertical-align: middle;}
                    table.plugin-options-table td+td {width: auto}
                    table.plugin-options-table td > p {margin-top: 0; margin-bottom: 0;}
                    table.plugin-options-table tr.has-error * {color: red;}
                </style>
                <table class="plugin-options-table"><tbody>
                <?php
                if ($optionMetaData != null) {
                    foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                        if (!isset($aOptionMeta[2]))
                            $aOptionMeta[2] = null;

                        if (!isset($aOptionMeta[3]))
                            $aOptionMeta[3] = null;

                        if ($aOptionMeta[3] === 'hidden')
                            continue;
                        ?>
                            <tr valign="top"<?php 
                                if (in_array($aOptionKey, $this->errors))
                                    echo ' class="has-error"';
                                ?>>
                                <th scope="row"><p><label for="<?php echo $aOptionKey ?>"><?php echo $aOptionMeta[0]; ?></label></p></th>
                                <td>
                                    <?php $this->createFormControl($aOptionKey, $aOptionMeta[2], $this->getOption($aOptionKey), $aOptionMeta[3]); ?>
                                    <?php
                                    if (in_array($aOptionKey, $this->errors)) : ?>
                                        <p>Organization could not be found</p>
                                    <?php
                                    endif;
                                    ?>
                                </td>
                            </tr>
                        <?php
                    }
                }
                ?>
                </tbody></table>
                <p class="submit">
                    <input type="submit" class="button-primary"
                           value="<?php _e('Save Changes', 'grep-github-contributors') ?>"/>
                </p>
            </form>

            <h2>Plugin status</h2>
            <?php 
            if (false === wp_next_scheduled( 'grep-github-contributors-get-members' )) : ?>
                <p>Plugin is not running</p>
            <?php else : ?>
                <p>Plugin is running</p>
            <?php endif; ?>

            <h2>Log</h2>
            <table  class="plugin-options-table">
                <tr>
                    <th>Date</th>
                    <th>Message</th>
                </tr>
                <?php

                global $wpdb;
                $results = $wpdb->get_results("SELECT * FROM " . $this->prefixTableName('log') . " ORDER BY id DESC LIMIT 0, 1000");

                foreach($results as $row) { 
                    ?>
                    <tr>
                        <td><?php echo date('d.m.Y H:i:s', $row->date); ?></td>
                        <td><?php echo $row->message; ?></td>
                    </tr>
                <?php
                }

                ?>
            </table>

            <h2><?php _e('System Settings', 'grep-github-contributors'); ?></h2>
            <table cellspacing="1" cellpadding="2"><tbody>
            <tr><td><?php _e('System', 'grep-github-contributors'); ?></td><td><?php echo php_uname(); ?></td></tr>
            <tr><td><?php _e('PHP Version', 'grep-github-contributors'); ?></td>
                <td><?php echo phpversion(); ?>
                <?php
                if (version_compare('5.2', phpversion()) > 0) {
                    echo '&nbsp;&nbsp;&nbsp;<span style="background-color: #ffcc00;">';
                    _e('(WARNING: This plugin may not work properly with versions earlier than PHP 5.2)', 'grep-github-contributors');
                    echo '</span>';
                }
                ?>
                </td>
            </tr>
            <tr><td><?php _e('MySQL Version', 'grep-github-contributors'); ?></td>
                <td><?php echo $this->getMySqlVersion() ?>
                    <?php
                    echo '&nbsp;&nbsp;&nbsp;<span style="background-color: #ffcc00;">';
                    if (version_compare('5.0', $this->getMySqlVersion()) > 0) {
                        _e('(WARNING: This plugin may not work properly with versions earlier than MySQL 5.0)', 'grep-github-contributors');
                    }
                    echo '</span>';
                    ?>
                </td>
            </tr>
            </tbody></table>
        </div>
        <?php

    }

    /**
     * Helper-function outputs the correct form element (input tag, select tag) for the given item
     * @param  $aOptionKey string name of the option (un-prefixed)
     * @param  $aOptionMeta mixed meta-data for $aOptionKey (either a string display-name or an array(display-name, option1, option2, ...)
     * @param  $savedOptionValue string current value for $aOptionKey
     * @return void
     */
    protected function createFormControl($aOptionKey, $aOptionMeta, $savedOptionValue, $type = 'text') {
        if (is_array($aOptionMeta) && count($aOptionMeta) >= 2) { // Drop-down list
            $choices = array_slice($aOptionMeta, 1);
            ?>
            <p><select name="<?php echo $aOptionKey ?>" id="<?php echo $aOptionKey ?>">
            <?php
                            foreach ($choices as $aChoice) {
                $selected = ($aChoice == $savedOptionValue) ? 'selected' : '';
                ?>
                    <option value="<?php echo $aChoice ?>" <?php echo $selected ?>><?php echo $this->getOptionValueI18nString($aChoice) ?></option>
                <?php
            }
            ?>
            </select></p>
            <?php

        }
        else { // Simple input field
            ?>
            <p><input type="<?php echo $type; ?>" name="<?php echo $aOptionKey ?>" id="<?php echo $aOptionKey ?>"
                      value="<?php echo esc_attr($savedOptionValue) ?>" size="50"/></p>
            <?php

        }
    }

    /**
     * Override this method and follow its format.
     * The purpose of this method is to provide i18n display strings for the values of options.
     * For example, you may create a options with values 'true' or 'false'.
     * In the options page, this will show as a drop down list with these choices.
     * But when the the language is not English, you would like to display different strings
     * for 'true' and 'false' while still keeping the value of that option that is actually saved in
     * the DB as 'true' or 'false'.
     * To do this, follow the convention of defining option values in getOptionMetaData() as canonical names
     * (what you want them to literally be, like 'true') and then add each one to the switch statement in this
     * function, returning the "__()" i18n name of that string.
     * @param  $optionValue string
     * @return string __($optionValue) if it is listed in this method, otherwise just returns $optionValue
     */
    protected function getOptionValueI18nString($optionValue) {
        switch ($optionValue) {
            case 'true':
                return __('true', 'grep-github-contributors');
            case 'false':
                return __('false', 'grep-github-contributors');

            case 'Administrator':
                return __('Administrator', 'grep-github-contributors');
            case 'Editor':
                return __('Editor', 'grep-github-contributors');
            case 'Author':
                return __('Author', 'grep-github-contributors');
            case 'Contributor':
                return __('Contributor', 'grep-github-contributors');
            case 'Subscriber':
                return __('Subscriber', 'grep-github-contributors');
            case 'Anyone':
                return __('Anyone', 'grep-github-contributors');
        }
        return $optionValue;
    }

    /**
     * Query MySQL DB for its version
     * @return string|false
     */
    protected function getMySqlVersion() {
        global $wpdb;
        $rows = $wpdb->get_results('select version() as mysqlversion');
        if (!empty($rows)) {
             return $rows[0]->mysqlversion;
        }
        return false;
    }

    /**
     * If you want to generate an email address like "no-reply@your-site.com" then
     * you can use this to get the domain name part.
     * E.g.  'no-reply@' . $this->getEmailDomain();
     * This code was stolen from the wp_mail function, where it generates a default
     * from "wordpress@your-site.com"
     * @return string domain name
     */
    public function getEmailDomain() {
        // Get the site domain and get rid of www.
        $sitename = strtolower($_SERVER['SERVER_NAME']);
        if (substr($sitename, 0, 4) == 'www.') {
            $sitename = substr($sitename, 4);
        }
        return $sitename;
    }
}

