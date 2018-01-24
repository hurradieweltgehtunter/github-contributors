<?php

use Github\HttpClient\Message\ResponseMediator;
include_once('GrepGithubContributors_LifeCycle.php');

class GrepGithubContributors_Plugin extends GrepGithubContributors_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'ATextInput' => array(__('Enter in some text', 'my-awesome-plugin')),
            'AmAwesome' => array(__('I like this awesome plugin', 'my-awesome-plugin'), 'false', 'true'),
            'CanDoSomething' => array(__('Which user role can do something', 'my-awesome-plugin'),
                                        'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'Grep github contributors';
    }

    protected function getMainPluginFileName() {
        return 'grep-github-contributors.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
                global $wpdb;
                $tableName = $this->prefixTableName('oc-contributors');
                $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
                    `id` INTEGER NOT NULL
                    ``
                    ");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
                global $wpdb;
                $tableName = $this->prefixTableName('oc-contributors');
                $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }

        add_action( 'init', array($this, 'registerPostType') );

        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37


        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39
        add_shortcode('get-contributors', array($this, 'doGetContributors'));

        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41

    }

    public function registerPostType() {
      $labels = array(
          'name'                => _x( 'Contributors', 'Post Type General Name', 'getGithubContributorsPlugin' ),
          'singular_name'       => _x( 'Contributor', 'Post Type Singular Name', 'getGithubContributorsPlugin' ),
          'menu_name'           => __( 'GitHub Contributors', 'getGithubContributorsPlugin' ),
          'parent_item_colon'   => __( 'Parent Contributor', 'getGithubContributorsPlugin' ),
          'all_items'           => __( 'All Contributors', 'getGithubContributorsPlugin' ),
          'view_item'           => __( 'View Contributor', 'getGithubContributorsPlugin' ),
          'add_new_item'        => __( 'Add New Contributor', 'getGithubContributorsPlugin' ),
          'add_new'             => __( 'Add New', 'getGithubContributorsPlugin' ),
          'edit_item'           => __( 'Edit Contributor', 'getGithubContributorsPlugin' ),
          'update_item'         => __( 'Update Contributor', 'getGithubContributorsPlugin' ),
          'search_items'        => __( 'Search Contributor', 'getGithubContributorsPlugin' ),
          'not_found'           => __( 'Not Found', 'getGithubContributorsPlugin' ),
          'not_found_in_trash'  => __( 'Not found in Trash', 'getGithubContributorsPlugin' ),
      );
       
      // Set other options for Custom Post Type
       
      $args = array(
          'label'               => __( 'contributor', 'getGithubContributorsPlugin' ),
          'description'         => __( 'ownCloud\'s GitHub contributors', 'getGithubContributorsPlugin' ),
          'labels'              => $labels,
          // Features this CPT supports in Post Editor
          'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
          /* A hierarchical CPT is like Pages and can have
          * Parent and child items. A non-hierarchical CPT
          * is like Posts.
          */ 
          'hierarchical'        => false,
          'public'              => true,
          'show_ui'             => true,
          'show_in_menu'        => true,
          'show_in_nav_menus'   => true,
          'show_in_admin_bar'   => true,
          'menu_position'       => 5,
          'can_export'          => true,
          'has_archive'         => true,
          'exclude_from_search' => false,
          'publicly_queryable'  => true,
          'capability_type'     => 'page',
      );
       
      // Registering your Custom Post Type
      register_post_type( 'contributor', $args );
  }

  public function doGetContributors() {
    require_once __DIR__ . '/vendor/autoload.php';

    $client = new \Github\Client();


    

    // $organizationApi = $client->api('organization');

    // $paginator  = new Github\ResultPager($client);
    // $parameters = array('owncloud');
    // $members     = $paginator->fetchAll($organizationApi, 'members', $parameters);
    $members = $client->api('organizations')->members()->all('owncloud');

    $pagination = ResponseMediator::getPagination($client->getLastResponse());
    $count = 0;
    while(isset($pagination['next'])) {
      $page = substr($pagination['next'], strpos($pagination['next'], 'page=') + 5);
      $delta = $client->api('organizations')->members()->all('owncloud', null, 'all', null, $page);

      $temp = array_merge($members, $delta);
      $temp = $members;

      $count++;
      if($count > 5) {
        break;
      }
    }

    echo '<pre>';
    print_r( $members );
    echo '</pre>';
    exit();
    $count = 0;
    foreach($members as $member) {
      $e = get_page_by_title( $member['login'], 'OBJECT', 'contributor' );

      if (null === $e) {
        // contributor is not found
        echo $member['login'] . ' not in DB<br />';

        $user = $client->api('user')->show('DeepDiver1975'); //$member['login']);
        
        
        if ($user['name'] !== '') {
          $name = $user['name'];
        } else {
          $name = $user['login'];
        }

        $id = wp_insert_post(array(
          'post_excerpt' => $user['bio'] . ' ',
          'post_title' => $name,
          'post_status' => 'publish',
          'post_type' => 'contributor',
          'comment_status' => 'closed',
          'meta_input' => array(
            'blog' => $user['blog'],
            'github' => $user['html_url'],
            'location' => $user['location'],
            'avatar' => $user['avatar_url'],
            'visible' => 1
          )
        ));
      }  

      break;
    }

    

    $client = new \Github\Client();
    $repositories = $client->api('organizations')->members()->all('owncloud');



    // $repositories = $client->api('user')->show('brantje');

    // $client   = new Github\Client();
    // $response = $client->getHttpClient()->get('/users/hurradieweltgehtunter/events?q=type:CommitComment');
    // $response = $client->getHttpClient()->get('/orgs/owncloud/events');

    // $events     = Github\HttpClient\Message\ResponseMediator::getContent($response);

    // echo '<pre>';
    // print_r( $events );
    // echo '</pre>';
    // return 'Hello Word!123';
  }
}



