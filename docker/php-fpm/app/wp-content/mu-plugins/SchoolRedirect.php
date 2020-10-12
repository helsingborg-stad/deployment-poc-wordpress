<?php

namespace SchoolRedirect;

class SchoolRedirect
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));

        // Form
        add_action('wpmu_options', array($this, 'addEndpointOptionField'));
        add_action('update_wpmu_options', array($this, 'saveEndpointUrl'));
    }

    /**
     * Initializes the redirect process by getting the sites and stuff
     * @return void
     */
    public function init()
    {
        $response = wp_cache_get('sites', 'school-redirect');

        if (!$response) {
            $response = $this->getSites();
        }

        $response = apply_filters('siteRedirect', $response);

        $this->maybeRedirect($response);
    }

    /**
     * Redirect if needed
     * @param  array $sites List of sites
     * @return bool
     */
    public function maybeRedirect(array $sites) : bool
    {
        $currentPath = trim($this->currentPath(), '/');

        foreach ($sites as $site) {
            $subdomain = explode('.', $site->domain);
            $subdomain = reset($subdomain);

            if (substr($currentPath, 0, strlen($subdomain)) != $subdomain) {
                continue;
            }

            wp_redirect($site->url."/". str_ireplace($subdomain."/", "", $currentPath . "/"), '302');
            exit;
        }

        return false;
    }

    /**
     * Get the current url path
     * @return string
     */
    public function currentPath() : string
    {
        return trailingslashit($_SERVER['REQUEST_URI']);
    }

    /**
     * Get list of sites from api endpoint
     * @return array
     */
    public function getSites() : array
    {
        $endpointUrl = get_site_option('school-redirect-endpoint');

        if (!$endpointUrl) {
            return array();
        }

        $request = wp_remote_get($endpointUrl);
        $response = wp_remote_retrieve_body($request);
        $response = json_decode($response);

        if (!$response) {
            return array();
        }

        // Remove main site from array
        $response = array_filter($response, function ($site) {
            return !is_main_site($site->blog_id);
        });

        wp_cache_add('sites', $response, 'school-redirect', 60*60*24);

        return $response;
    }

    /**
     * Add field for endpoint url to network settings
     */
    public function addEndpointOptionField()
    {
        $endpointUrl = get_site_option('school-redirect-endpoint');

        echo '<h2>' . __('Subnetwork redirect') . '</h2>';
        echo '<table class="form-table"><tbody><tr>
            <th scope="row"><label for="school-redirect-endpoint">Rest endpoint url</label></th>
            <td><input type="text" class="widefat" name="school-redirect-endpoint" id="school-redirect-endpoint" value="' . $endpointUrl . '"><p class="description" id="upload-filetypes-desc">Example: http://helsingborg.se/wp-json/wp/v2/sites/</p></td>
        </tr></table>';
    }

    /**
     * Saves the endpoint url as site option
     * @return void
     */
    public function saveEndpointUrl()
    {
        if (!isset($_POST['school-redirect-endpoint'])) {
            return;
        }

        if (empty($_POST['school-redirect-endpoint'])) {
            delete_site_option('school-redirect-endpoint');
            return;
        }

        update_site_option('school-redirect-endpoint', $_POST['school-redirect-endpoint']);
    }
}

new \SchoolRedirect\SchoolRedirect();
