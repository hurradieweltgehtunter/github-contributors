<?php

use Github\HttpClient\Message\ResponseMediator;
include_once('GrepGithubContributors_LifeCycle.php');
require_once __DIR__ . '/vendor/autoload.php';

class GrepGithubContributors_Plugin extends GrepGithubContributors_LifeCycle {

    function __construct() {
      $this->client = new \Github\Client();
      $this->client->authenticate($this->getOption('github-client-id'), $this->getOption('github-client-secret'), Github\Client::AUTH_URL_CLIENT_ID);
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        /*
        option name => 0: Display Name
                       1: default value to save on install
                       2: optional values for select field
        */


        return array(
            // '_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'github-client-id' => array(
              __('GitHub Client ID', 'grep-github-contributors'),
              null,
              null,
              null,
              'Please enter your Github client ID'
              ),
            'github-client-secret' => array(
              __('GitHub Client secret', 'grep-github-contributors'),
              null,
              null,
              null,
              'Please enter your Github client secret'
            ),
            'github-organization' => array(
              __('GitHub Organization', 'grep-github-contributors'),
              null,
              null,
              null,
              'Organization could not be found'
              ),
            'post-type-rewrite' => array(
              __('Available via Slug', 'grep-github-contributors')),
            'log-duration' => array(
              __('How long should the log be stored? (days)',
                'grep-github-contributors'),
                5,
                null,
                'number', 'Please enter a number bigger then 0'),
            'last_fetched' => array(
              __('Last fetched:', 'grep-github-contributors'), 0, null, 'hidden'),
            'current-action' => array(
              __('Current action', 'grep-github-contributors'), 'idle', null, 'hidden')
        );
    }

    protected function initOptions() {
      $options = $this->getOptionMetaData();
      if (!empty($options)) {
        foreach ($options as $key => $arr) {
          if (!isset($arr[1]))
            $arr[1] = '';

          $this->addOption($key, $arr[1]);
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
      $tableName = $this->prefixTableName('log');
      $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` int(11) NOT NULL,
          `message` text NOT NULL,
          PRIMARY KEY (id)
          )
          ");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
      global $wpdb;
      $tableName = $this->prefixTableName('log');
      $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }

    protected function otherUninstall() {
      foreach($this->getOptionMetaData() as $key => $option);
        $this->deleteOption( $key );

      global $wpdb;
      // delete all posts by post type.
      $sql = 'DELETE `posts`, `pm`
          FROM `' . $wpdb->prefix . 'posts` AS `posts` 
          LEFT JOIN `' . $wpdb->prefix . 'postmeta` AS `pm` ON `pm`.`post_id` = `posts`.`ID`
          WHERE `posts`.`post_type` = \'contributor\'';
      $result = $wpdb->query($sql);
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

        // Add Cronjobs
        add_action('grep-github-contributors-get-members', array($this, 'startBaseJob'));
        add_action('grep-github-contributors-get-member-activity', array($this, 'fetchUsersActivities'));
        add_action('grep-github-contributors-get-member-feed', array($this, 'fetchBlogFeeds'));



        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39

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

      if ($this->getOption('post-type-rewrite') !== '') {
        flush_rewrite_rules();
        $args['rewrite'] = array('slug' => $this->getOption('post-type-rewrite'), 'with_front' => false);
      }

      // Registering your Custom Post Type
      register_post_type( 'contributor', $args );
  }

  public function log($message) {
    global $wpdb;
    $wpdb->query("INSERT INTO " . $this->prefixTableName('log') . " (id, date, message) VALUES (NULL, '" . time() . "', '$message')");
  }

  public function startBaseJob() {
    // keep the log clean
    global $wpdb;
    $results = $wpdb->query("DELETE FROM " . $this->prefixTableName('log') . " WHERE date < " . (time() - (86400 * $this->getOption('log-duration'))));

    $this->log('starting Plugin run');
    // some other plugin job is running
    if ($this->getOption('current-action') !== 'idle') {
      $this->log('Plugin not idling - aborting. current action: ' . $this->getOption('current-action'));
      wp_die();
    }

    $this->updateOption('current-action', 'baseJob running');

    $start = microtime(true);
    
    // delete users who didn't get updated for at least 2 days (=> left the organization)
    $args = array(
      'post_type' => 'contributor',
      'date_query' => array(
        array(
          'column' => 'post_modified_gmt',
          'before' => '2 days ago',
        ),
      ),
      'posts_per_page' => -1
    );

    $query = new WP_Query( $args );
    if ( $query->have_posts() ) {
      while ( $query->have_posts() ) {
        $query->the_post();
        wp_delete_post( get_the_ID(), true );
      }
    }

    // Fetch Member list from github
    $members = $this->client->api('organizations')->members()->all($this->getOption('github-organization'));
    $pagination = ResponseMediator::getPagination($this->client->getLastResponse());

    while(isset($pagination['next'])) {
      $page = substr($pagination['next'], strpos($pagination['next'], 'page=') + 5);
      if (strpos($page, '&') > 0)
        $page = substr($page, 0, strpos($page, '&'));

      $delta = $this->client->api('organizations')->members()->all($this->getOption('github-organization'), null, 'all', null, $page);

      $pagination = ResponseMediator::getPagination($this->client->getLastResponse());

      $temp = array_merge($members, $delta);
      $members = $temp;
    }

    $this->log('found ' . count($members) . ' members');

    $insert = 0;
    $update = 0;

    // get user details
    foreach($members as $member) {
      $user = $this->getMemberDetails($member['login']);
      print_r($user);
      $e = get_page_by_title( $member['login'], 'OBJECT', 'contributor' );

      // validate data
      if(count($user['blog']) > 1 && substr($user['blog'], -1) !== '/')
        $user['blog'] = $user['blog'] . '/';

      if (null === $e) {
        // contributor is not in DB -> insert
        $id = $this->insertContributor($user);
        $this->log('inserting user ' . $user['login']);
        $insert++;
      } else {
        // contributor is already in DB -> update
        $id = $this->updateContributor($e->ID, $user);
        $this->log('updating user ' . $user['login']);
        $update++;
      }

      if ($user['blog'] !== '') {
        $feed = $this->getContributorsFeedUrl($user);
        if (false !== $feed) {
          update_post_meta( $id, 'feed', $feed);
        }
      }
    }

    $this->log('done inserting users|inserted:' . $insert . '|updated:' . $update);

    //run initial fetch jobs
    if( $this->getOption('last_fetched') == 0) {
      if (!wp_next_scheduled( 'grep-github-contributors-get-member-activity' ) ) {
        $this->log('setting up initial Activity fetch');
        wp_schedule_single_event( time() - 1, 'grep-github-contributors-get-member-activity');
      }

      if (!wp_next_scheduled( 'grep-github-contributors-get-member-feed' ) ) {
        $this->log('setting up initial feed fetch');
        wp_schedule_single_event( time() - 1, 'grep-github-contributors-get-member-feed');
      }

      spawn_cron();
    }

    $this->updateOption('last_fetched', time());
    $end = microtime(true);
    $this->log('BaseJob done in ' . ($end - $start) . ' seconds');
    $this->updateOption('current-action', 'idle');
  }

  public function getMemberDetails($username) {
    return $this->client->api('user')->show($username);
  }

  public function insertContributor($user) {
    return wp_insert_post(array(
      'post_excerpt' => $user['bio'] . ' ', // add space as excerpt must not be empty in wordpress
      'post_title' => $user['login'],
      'post_status' => 'publish',
      'post_type' => 'contributor',
      'comment_status' => 'closed',
      'meta_input' => array(
        'blog' => $user['blog'],
        'feed' => '',
        'github' => $user['html_url'],
        'location' => $user['location'],
        'avatar' => $user['avatar_url'],
        'company' => $user['company'],
        'public_repos' => $user['public_repos'],
        'public_gists' => $user['public_gists'],
        'visible' => 1,
        'name' => $user['name'],
        'last_activity_fetch' => 0,
        'last_feed_fetch' => 0,
        'feedposts' => '',
        'oc-index' => 0,
        'nc-index' => 0
      )
    ));
  }

  public function updateContributor($id, $user) {
    wp_update_post(array(
      'ID' => $id,
      'post_excerpt' => $user['bio'] . ' ', // add space as excerpt must not be empty in wordpress
      'meta_input' => array(
        'blog' => $user['blog'],
        'github' => $user['html_url'],
        'location' => $user['location'],
        'avatar' => $user['avatar_url'],
        'company' => $user['company'],
        'public_repos' => $user['public_repos'],
        'public_gists' => $user['public_gists'],
        'name' => $user['name']
      )
    ));

    return $id;
  }

  /**
  Search for a users RSS Feed
  */
  public function getContributorsFeedUrl($user) {
    $logtext = 'searching feeds on url ' . $user['blog']. ':';
    $feed = $this->feedSearch($user['blog']);
    if (false !== $feed) {
      if (filter_var($feed, FILTER_VALIDATE_URL) === FALSE) {
        $feed = $user['blog'] . str_replace('/', '', $feed);
      }
      $this->log($logtext . 'feed found:' . $feed);
      return $feed;
    } else {
      $this->log($logtext . 'no feed found');
      return false;
    }
  }

  public function fetchUsersActivities() {
    $this->updateOption('current-action', 'get User Activities');
    $this->log('starting userActivity run');
    $args = array(
      'post_type' => 'contributor',
      'posts_per_page' => 10,
      'meta_query' => array(
        array(
          'key'     => 'last_activity_fetch',
          'value'   => time() - 3600,
          'compare' => '<',
        )
      )
    );
    $the_query = new WP_Query( $args );

    if ( $the_query->have_posts() ) {

      while ( $the_query->have_posts() ) {
        $the_query->the_post();
        $githubActivity = $this->fetchUserActivities(get_the_title());

        $user = array(
          'ID'           => get_the_ID(),
          'post_content' => $githubActivity
        );
        $this->log('fetched activities for ' . get_the_title());
        wp_update_post( $user );
        update_post_meta( get_the_ID(), 'last_activity_fetch', time());

        $this->calcUserStats(get_the_ID(), $githubActivity);
      }
      wp_reset_postdata();

      $this->log('ended activity update run on 10 users. scheduling next round');
      wp_schedule_single_event( time() - 1, 'grep-github-contributors-get-member-activity');
      spawn_cron();
    } else {
      $this->log('nothing else to to. schedule next activity fetch run in 1 hour at ' . (date('d.m.Y H:i', (time() + 3600))));
      wp_schedule_single_event( time() + 3600, 'grep-github-contributors-get-member-activity');
      $this->updateOption('current-action', 'idle');
    }
  }

  function fetchUserActivities ($username) {
    $response = $this->client->getHttpClient()->get('/users/' . $username . '/events/public');
    $events   = Github\HttpClient\Message\ResponseMediator::getContent($response);

    $content = '<ul>';
    $rest = array();
    $text = '';

    foreach($events as $key=>$e) {
      $date = date('Y-m-d', strtotime($e['created_at'])) . ': ';

      try {
        // github events: https://developer.github.com/v3/activity/events/types
        switch($e['type']) {
          case 'PushEvent':
            if (count($e['payload']['commits']) > 0) {
              $url = str_replace('https://api.github.com', '', $e['payload']['commits'][0]['url']);
              $response = $this->client->getHttpClient()->get($url);
              $commit     = Github\HttpClient\Message\ResponseMediator::getContent($response);

              $text .= '<li class="repo-push">' . $date . ' <a href="' . $commit['html_url'] . '" target="blank">' . $username . ' pushed to repository ' . $e['repo']['name'] . '</a></li>';
            }
            break;

          case 'CreateEvent':
            $url = str_replace('https://api.github.com', '', $e['repo']['url']);
            $response = $this->client->getHttpClient()->get($url);
            $entity     = Github\HttpClient\Message\ResponseMediator::getContent($response);

            $text .= '<li class="create-' . $e['payload']['ref_type'] . '">' . $date . ' <a href="' . $entity['html_url'] . '" target="blank">' . $username . ' created a ' . $e['payload']['ref_type'] . ' in repository ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'IssueCommentEvent':
            $text .= '<li class="comment-discussion">' . $date . ' <a href="' . $e['payload']['issue']['html_url'] . '" target="blank">' . $username . ' commented on issue #' . $e['payload']['issue']['number'] . ' in ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'IssuesEvent':
            switch($e['payload']['action']) {
              case 'opened':
                $class = 'opened';
              case 'closed':
                $class = 'closed';
              case 'reopened':
                $class = 'reopened';
              default:
                $text .= '<li class="issue-' . $class . '">' . $date . ' <a href="' . $e['payload']['issue']['html_url'] . '" target="blank">' . $username . ' ' . $e['payload']['action'] . ' issue #' . $e['payload']['issue']['number'] . ': ' . $e['payload']['issue']['title'] . ' in repository ' . $e['repo']['name'] . '</a></li>';
                break;
            }
            break;

          case 'CommitCommentEvent':
            $text .= '<li class="comment-discussion">' . $date . ' <a href="' . $e['payload']['comment']['html_url'] . '" target="blank">' . $username . ' commented on a commit in repository ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'PullRequestEvent':
            $text .= '<li class="pull-request">' . $date . ' <a href="' . $e['payload']['pull_request']['html_url'] . '" target="blank">' . $username . ' ' . $e['payload']['action'] . ' pull request #' . $e['payload']['pull_request']['number'] . ' "' . $e['payload']['pull_request']['title'] . '" in ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'WatchEvent':
            $text .= '<li class="star">' . $date . ' <a href="https://github.com/' . $e['repo']['name'] . '" target="blank">' . $username . ' starred repository ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'ForkEvent':
            // User forked a repo
            $text .= '<li class="repo-forked">' . $date . ' <a href="' . $e['payload']['forkee']['html_url'] . '" target="blank">' . $username . ' forked repository ' . $e['repo']['name'] . ' into ' . $e['payload']['forkee']['name'] . '</a></li>';
            break;

          case 'PullRequestReviewCommentEvent':
            // User commented on a pull request
            $text .= '<li class="comment-discussion">' . $date . ' <a href="' . $e['payload']['comment']['html_url'] . '" target="blank">' . $username . ' commented a diff in pull request #' . $e['payload']['pull_request']['number'] . ' in ' . $e['repo']['name'] . '</a></li>';
            break;

          case 'DeleteEvent':
            // deleted branch or tag
            break;

          case 'ReleaseEvent':
            $text .= '<li class="release">' . $date . ' <a href="' . $e['payload']['release']['html_url'] . '" target="blank">' . $username . ' ' . $e['payload']['action'] . ' release ' . $e['payload']['release']['tag_name'] . ' in repository ' . $e['repo']['name'] . '</a></li>';
            break;

          default:
            $rest[] = $e;
            break;
        }

        $content .= $text;
      } catch (Exception $e) {

      }
    }

    $content .= '</ul>';
    // do sth. with $rest = uncatched events

    return $content;
  }

  protected function calcUserStats($postId, $githubActivity) {
    $word_count = explode(' ', $githubActivity);
    $word_count = count($word_count);

    $oc_index = number_format((substr_count(strtolower($githubActivity), 'owncloud') / $word_count * 100), 2);
    $nc_index = number_format((substr_count(strtolower($githubActivity), 'nextcloud') / $word_count * 100), 2);

    update_post_meta( $postId, 'oc-index', $oc_index);
    update_post_meta( $postId, 'nc-index', $nc_index);
  }


  /**
  Searches a given URL for RSS feeds
  */
  public function feedSearch($url) {
    $start = microtime(true);
    $raw = @file_get_contents($url);
    if ( false !== $raw) {
      if($html = @DOMDocument::loadHTML($raw)) { // this is really slow, better ways?

        $xpath = new DOMXPath($html);
        $feeds = $xpath->query("//head/link[@href][@type='application/rss+xml']/@href");

        if ($feeds->length > 0) {
          $results = array();

          foreach($feeds as $feed) {
            $results[] = $feed->nodeValue;
          }

          return $results[0];
        } else {
          return false;
        }
      }
      return false;
    }

    return false;
  }

  public function fetchBlogFeeds() {
    $this->updateOption('current-action', 'fetch blog feeds');
    $start = microtime(true);

    set_time_limit(0);
    $args = array(
      'post_type' => 'contributor',
      'posts_per_page' => 10,
      'meta_query' => array(
        'relation' => 'AND',
        array(
          'key'     => 'last_feed_fetch',
          'value'   => time() - 86400,
          'compare' => '<'
        ),
        array(
          'key'     => 'blog',
          'value'   => '',
          'compare' => '!='
        ),
        array(
          'key'     => 'feed',
          'value'   => '',
          'compare' => '!='
        )
      )
    );
    $the_query = new WP_Query( $args );

    if ( $the_query->have_posts() ) {
      $this->log('starting blog feed fetch run for ' . $the_query->post_count . ' blogs');

      while ( $the_query->have_posts() ) {

        $the_query->the_post();
        $feedurl  = get_post_meta(get_the_ID(), 'feed')[0];
        $this->log('fetching feed for ' . get_the_title() . ': ' . $feedurl);

        $xml      = simplexml_load_file($feedurl);
        $posts    = array();
        $count    = 0;
        foreach ($xml->channel->item as $item) {
          $posts[] = array(
            'title' => (string) $item->title,
            'link' => (string) $item->link,
            'date' => (string) $item->pubDate
          );

          $count++;

          if ($count >= 5)
            break;
        }
        $this->log('fetched ' . $count . ' items for ' . get_the_title() . ': ' . $feedurl);
        update_post_meta(get_the_ID(), 'feedposts', $posts);
        update_post_meta(get_the_ID(), 'last_feed_fetch', time());
      }

      wp_schedule_single_event( time() - 1, 'grep-github-contributors-get-member-feed');
      spawn_cron();
    } else {
      wp_schedule_single_event( time() + 3600, 'grep-github-contributors-get-member-feed');
      $this->log('ended blog feed fetch in ' . (microtime(true) - $start) . ' seconds ');
      $this->updateOption('current-action', 'idle');
    }

    $this->log('ended blog feed fetch run ');

  }

  public function getLatestFeed($feedUrl) {

  }

  public function getContributorsList() {
    $contributors = array();
    $args = array(
      'post_type' => 'contributor',
      'posts_per_page' => -1
    );
    $the_query = new WP_Query( $args );

    if ( $the_query->have_posts() ) {
      while ( $the_query->have_posts() ) {
        $the_query->the_post();
        $metas = get_post_meta(get_the_ID());

        $c = array(
          'id' => get_the_ID(),
          'title' => get_the_title(),
          'excerpt' => get_the_excerpt(),
          'link' => get_the_permalink()
        );

        foreach($metas as $key=>$value) {
          $c[$key] = $value[0];
        }

        $contributors[] = $c;
      }
    }

    return $contributors;
  }
}
