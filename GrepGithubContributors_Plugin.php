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
            'github-client-id' => array(__('Github Client ID', 'grep-github-contributors')),
            'github-client-secret' => array(__('Github Client secret', 'grep-github-contributors')),
            'CanDoSomething' => array(__('Which user role can do something', 'grep-github-contributors'),
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
    echo '->' . $this->getOption('last_fetched');
    if ( (time() - 3600) <= $this->getOption('last_fetched') ) {
      echo 'time not exceeded';
      return false;
    }

    require_once __DIR__ . '/vendor/autoload.php';

    $client = new \Github\Client();
    $client->authenticate($this->getOption('github-client-id'), $this->getOption('github-client-secret'), Github\Client::AUTH_URL_CLIENT_ID);

    $members = $client->api('organizations')->members()->all('owncloud');
    $pagination = ResponseMediator::getPagination($client->getLastResponse());

    while(isset($pagination['next'])) {
      $page = substr($pagination['next'], strpos($pagination['next'], 'page=') + 5);
      if (strpos($page, '&') > 0)
        $page = substr($page, 0, strpos($page, '&'));

      $delta = $client->api('organizations')->members()->all('owncloud', null, 'all', null, $page);

      $pagination = ResponseMediator::getPagination($client->getLastResponse());

      $temp = array_merge($members, $delta);
      $temp = $members;

    }

    $count = 0;
    foreach($members as $member) {

      $e = get_page_by_title( $member['login'], 'OBJECT', 'contributor' );

      if (null === $e) {
        // contributor is not found
        $user = $client->api('user')->show($member['login']);

        $id = wp_insert_post(array(
          'post_excerpt' => $user['bio'] . ' ',
          'post_title' => $user['login'],
          'post_status' => 'publish',
          'post_type' => 'contributor',
          'comment_status' => 'closed',
          'meta_input' => array(
            'blog' => $user['blog'],
            'github' => $user['html_url'],
            'location' => $user['location'],
            'avatar' => $user['avatar_url'],
            'visible' => 1,
            'name' => $user['name'],
            'last_activity_fetch' => 0
          )
        ));
      }
    }

    $this->updateOption(time());


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

  function getUserActivity($username) {
      $response = $client->getHttpClient()->get('/users/hurradieweltgehtunter/events/public');
      $events   = Github\HttpClient\Message\ResponseMediator::getContent($response);

      $content = '';
      $rest = array();

      foreach($events as $key=>$e) {
        $date = date('Y-m-d', strtotime($e['created_at']));

        switch($e['type']) {
          case 'PushEvent':
            $url = str_replace('https://api.github.com', '', $e['payload']['commits'][0]['url']);
            $response = $client->getHttpClient()->get($url);
            $commit     = Github\HttpClient\Message\ResponseMediator::getContent($response);

            $text = '<li>' . $date . ' <a href="' . $commit['html_url'] . '" target="blank">user pushed to repository ' . $e['repo']['name'] . '</a></li>';
            
            break;

          case 'CreateEvent':
            $url = str_replace('https://api.github.com', '', $e['repo']['url']);
            $response = $client->getHttpClient()->get($url);
            $entity     = Github\HttpClient\Message\ResponseMediator::getContent($response);

            $text = '<li>' . $date . ' <a href="' . $entity['html_url'] . '" target="blank">user created ' . $e['payload']['ref_type'] . ' ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'IssueCommentEvent':
            $text = '<li>' . $date . ' <a href="' . $e['payload']['issue']['html_url'] . '" target="blank">user commented on issue #' . $e['payload']['issue']['number'] . ' in ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'IssuesEvent':
            switch($e['payload']['action']) {
              case 'opened':
              case 'edited':
              case 'closed':
              case 'reopened':
                $text = '<li>' . $date . ' <a href="' . $e['payload']['issue']['html_url'] . '" target="blank">user ' . $e['payload']['action'] . ' issue #' . $e['payload']['issue']['number'] . ' in ' . $e['repo']['name'] . '</a></li>';
                break;
            }
            
            break;

          case 'CommitCommentEvent':
            $text = '<li>' . $date . ' <a href="' . $e['payload']['issue']['html_url'] . '" target="blank">user ' . $e['payload']['action'] . ' issue #' . $e['payload']['issue']['number'] . ' in ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'PullRequestEvent':
            $text = '<li>' . $date . ' <a href="' . $e['payload']['pull_request']['html_url'] . '" target="blank">user ' . $e['payload']['action'] . ' pull request #' . $e['payload']['pull_request']['number'] . ' "' . $e['payload']['pull_request']['title'] . '" in ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'DeleteEvent':
            // deleted branch or tag
            break;

          default:
            $rest[] = $e;
            break;
        }

        $content .= $text;

      }

      echo '<ul>' . $content . '</ul>';
      echo '<pre>';
      print_r( $rest );
      echo '</pre>';
      exit();
    }
}



