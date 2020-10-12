<?php
/*
Plugin Name: Split Test
Description: Creates a simple split test on WordPress front page. Define this (with your id's) in wp-config: define('SPLIT_TEST_PAGE_IDS', array(129, 697));.
Version:     1.0
Author:      Sebastian Thulin, Helsingborg Stad
*/

namespace SplitTest;

class SplitTest
{
    private $_postIds = array();
    private $_postType = "page";
    private $_sessionName = "split_test";
    private $_selectedPostId = null;

    /**
     * Init plugin
     *
     * @return void
     */
    public function __construct()
    {
        //Default to homepage uri
        if (!defined('SPLIT_TEST_URI')) {
            define("SPLIT_TEST_URI", "");
        }

        if (!$this->_matchingUri()) {
            return;
        }

        if (defined('SPLIT_TEST_PAGE_IDS') && is_array(SPLIT_TEST_PAGE_IDS) && !empty(SPLIT_TEST_PAGE_IDS)) {

            session_start();

            $this->_postIds = SPLIT_TEST_PAGE_IDS; // Adds post id array to object

            //Run filters
            add_filter('pre_get_posts', array($this, 'alterPostId'), 1);
            add_action('send_headers', array($this, 'bypassCache'), 1);
            add_action('wp_head', array($this, 'fixSeoIssues'), 1);
        }
    }

    /**
     * Print canonical if it's not the default frontpage
     *
     * @return void
     */
    public function fixSeoIssues()
    {
        //Check it's not default front page
        if ($this->_getPostId() == get_option('page_on_front')) {
            return;
        }

        //Print canonical url
        echo '<meta name="robots" content="noindex" />' . "\n";
        echo '<link rel="canonical" href="' . get_permalink($this->_getPostId()). '" />' . "\n";
    }

    /**
     * Send "do not cache" headers on frontpage
     *
     * @return void
     */
    public function bypassCache()
    {
        header('Pragma: no-cache');
        header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
    }

    /**
     * Manipulate front page id's
     *
     * @param object $query The query object for WordPress page fetch
     *
     * @return void
     */
    public function alterPostId($query)
    {

        //Check if main query
        if (!$query->is_main_query()) {
            return;
        }

        //Check if prevview
        if (is_preview()) {
            return;
        }

        //Set random page
        $query->set('post_type', $this->_postType);
        $query->set('page_id', $this->_getPostID());

    }

    /**
     * Add custom post types to Post submenu. (need Nested Pages plugin)
     *
     * @return int
     */
    private function _getPostId() : int
    {
        //Get stored page
        if (isset($_SESSION[$this->_sessionName]) && is_numeric($_SESSION[$this->_sessionName])) {
            return $_SESSION[$this->_sessionName];
        }

        //Get id stored for this instance
        if (!is_null($this->_selectedPostId)) {
            return $this->_selectedPostId;
        }

        //Randomize a new page
        $this->_selectedPostId = $this->_postIds[array_rand($this->_postIds)];

        //Store as future cookie
        return $_SESSION[$this->_sessionName] = $this->_selectedPostId;
    }

    /**
     * Check if defined uri
     *
     * @return bool
     */
    private function _matchingUri()
    {
        if (rtrim($_SERVER['REQUEST_URI'], "/") == rtrim(SPLIT_TEST_URI, "/")) {
            return true;
        }
        return false;
    }
}

new \SplitTest\SplitTest();
