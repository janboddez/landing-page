# Landing Page
Map any add-on domain to any WordPress post or page.

Lets authors, public speakers, artists (and anyone else) map any custom domain to a dedicated page on their WordPress-powered website.

Written from scratch, this plugin was nevertheless at least inspired by [Multiple Domain Mapping on Single Site](https://wordpress.org/plugins/multiple-domain-mapping-on-single-site/), by [Matthias Wagner](http://www.matthias-wagner.at), and takes some clues from Automattic's [Jetpack](https://github.com/Automattic/jetpack), both licensed under the GPLv2 (or later).

Know what's cool, too? Using this in combination with [Page Themes](https://github.com/janboddez/page-themes).

## Usage
You'll need to have all additional domains registered and pointing to your single WordPress install, through their DNS records and your web server configuration.

E.g., after having an additional domain's A record point to your server's IP address, you may need to create a VirtualHost or add-on domain, and point its Document Root to the directory WordPress is running from.

If your site uses HTTPS, as it probably should, you'll  want to install SSL certificates for all add-on domains, too. This plugin assumes both your main and any additional (sub)domains use the same protocol (_either_ HTTP _or_ HTTPS).

If after the new domain's DNS settings have propagated it correctly redirects to your WordPress homepage, go ahead and map it to _any_ of your site's URLs.

Pages, posts and custom post types are recommended targets, for which WordPress and this plugin support things like canonical URLs.

Although the Target autocomplete function typically only displays posts and pages, it _is_ possible to fill out anything! (It's possible to extend the autocomplete search, too, using a _filter_.)

Archive pages can be targets, too, but you may probably want to exclude them from search engine indexing—if they're not exluded already—to prevent duplicate content penalties.

## Settings
There's exactly one settings page (Settings > Landing Page), for both the actual mapping and a number of miscellaneous settings.

<img src="https://janboddez.tech/uploads/2020/03/landing-page-settings.png" width="1200">

Add an unlimited number of add-on domains, without trailing slash or `http(s)://`, and point them to any page on your WordPress site.

Subdomains work equally well, so you could have `portfolio.example.org` display the page at `http://example.org/portfolio/`.

You'll likely want **canonical URLs** to show the new domain, too, for search engine optimization purposes, so there's a setting for that.

Additionally, you can set up to **automatic redirects** for mapped pages.

**Note:** If everything's set up correctly and you keep seeing only the homepage, completely clear your browser's cache before trying again.

## Action and Filter Hooks
### `landing_page_post_types`
To have Custom Post Types included in the autocomplete search, use the following filter:
```
add_filter( 'landing_page_post_types', function( $post_types ) {
  $post_types = array(
    'post',
    'page',
    'my-custom-post-type',
  )

  return $post_types;
} );
```

Again, there's no visual checkboxes or anything for supported post types, as literally any URL can be a target. (Although, once more, posts, pages, or custom post types really make the most sense.) The filter above only affects the autocomplete function.

### `landing_page_host`
The plugin uses the `$_SERVER['HTTP_HOST']` variable to determine what to do. It's possible to override that behavior:
```
add_filter( 'landing_page_host', function( $host ) {
  if ( ! empty( $_SERVER['SERVER_NAME'] ) {
    $host = wp_unslash( $_SERVER['SERVER_NAME'] );
  }

  return $host;
} );
```
### `landing_page_mapping`
One last filter allows for overriding pretty much _everything_, so use it very carefully!
```
add_filter( 'landing_page_mapping', function( $mapping ) ) {
  // Remove support for certain domains.
  if ( array_key_exists( 'mydomain.org', $mapping ) ) {
    unset( $mapping['mydomain.org'] );
  }

  return $mapping;
}
```

## Some Remarks
### Retrieving Mapped Domains and Target URLs
It is entirely possible to determine whether mapping is in action, and for which domain and target URL:
```
$landing_page = Landing_Page::get_instance();

// Returns the requested domain.
$mapped_domain = $landing_page->get_domain();

// Is mapping active?
if ( '' !== $mapped_domain ) {
  // Do something.
  $domain_url = trailingslashit( wp_parse_url( home_url(), PHP_URL_SCHEME ) . '://' . $mapped_domain );
  echo '<a href="' . esc_url( $domain_url ) . '">Link to Mapped URL</a>';
}
```

```
$landing_page = Landing_Page::get_instance();

// Returns the target path.
$target_path = $landing_page->get_route();

// Is mapping active?
if ( '' !== $target_path ) {
  // Do something.
}

$original_url = home_url( $target_path );
```

### Sitemaps
If (and only if) canonical URL rewrites are enabled, mapped pages should probably be removed from sitemaps, so that they [stay valid](https://www.sitemaps.org/protocol.html#location).

Out of the box, Landing Page is compatible with a number of popular sitemap plugins, and handles this for you free of charge.

To disable Landing Page's sitemap filters (like, if you were writing your own), use:
```
remove_action(
  'jetpack_sitemap_skip_post',
  array( Landing_Page\Landing_page::get_instance()->get_filters(), 'jetpack_exclude_url' ),
  10
);

remove_filter(
  'wpseo_exclude_from_sitemap_by_post_ids',
  array( Landing_Page\Landing_page::get_instance()->get_filters(), 'wpseo_exclude_urls' )
);
```

### Multisite
If you intend to maintain several full-fledged microsites with different themes each, [WordPress multisite](https://wordpress.org/support/article/create-a-network/), which has native support for [domain mapping](https://wordpress.org/support/article/wordpress-multisite-domain-mapping/), may be what you're looking for. That said, since Gutenberg, WordPress's "new" block editor, it _is_ entirely possible to create an [entire range of sites](https://wpnux.godaddy.com/v2/) [off just one theme](https://wordpress.org/themes/go/).
