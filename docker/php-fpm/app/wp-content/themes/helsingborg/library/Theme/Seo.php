<?php
namespace Helsingborg\Theme;

class Seo
{
    const IMAGE_NAME = 'default-meta-image.png';

    public function __construct()
    {
        // Disable Wordpress automatic redirects. It's a bit too smart. We want 404s and not a
        // somewhat random redirect.
        remove_action('template_redirect', 'redirect_canonical');

        //Handla og:image data
        add_filter('the_seo_framework_ogimage_output', array($this, 'defaultImage'), 10, 2);
        add_filter('the_seo_framework_twitterimage_output', array($this, 'defaultImage'), 10, 2);

        // Remove plugin hidden comments.
        add_filter('the_seo_framework_indicator', '__return_false');

        // Customize robots.txt file
        add_filter('the_seo_framework_robots_txt_pro', array($this, 'seoRobots'), 99);
    }

    /**
     * Disallow Event post typ
     * @return string
     */
    public function seoRobots()
    {
        $output = "Disallow: /event/\r\n";
        return $output;
    }

    public function fallbackImage()
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['url'] . '/' . self::IMAGE_NAME;
    }

    public function defaultImage($image = '', $page_id = 0)
    {
        if (empty($page_id) || !has_post_thumbnail($page_id)) {
            $image = esc_url($this->fallbackImage());
        }

        return $image;
    }
}
