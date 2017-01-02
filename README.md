# Invalid Login Redirect - 1.0.0

**Tags:**              invalid, login, attempt, redirect, log, in <br />
**Requires at least:** WordPress v4.2 <br />
**Tested up to:**      WordPress v4.7 <br />
**Stable tag:**        1.0.0 <br />
**License:**           GPLv2 or later <br />
**License URI:**       [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)

## Description

Invalid Login Redirect is a WordPress plugin that allows you to specify the number of times a user can enter invalid credentials before being redirected to a custom page of your choosing.

Additionally, you can specify a custom message to display back to the user on the redirection landing page.

## Internationalization

All strings contained in **Invalid Login Redirect** are wrapped in the appropriate gettext filters and prepared for translation.

The text domain for the strings is `invalid-login-redirect`.

## Light Documentation

#### Filters

**Options**

Each option is individually filtered, allowing you to override them as needed.

**Option Defaults:**
- Login Limit: 3
- Redirect URL: site_url( '/wp-login.php?action=lostpassword' ); (example: https://www.example.com/wp-login.php?action=lostpassword)
- Error text: You have tried to login unsuccessfully 3 times. Have you forgotten your password?
- Error text color: #dc3232

**Option Filters:**
- `ilr_login_limit` - The number of times a user is allowed to try logging in before being redirected.
- `ilr_redirect_url` - The URL to the page the user will be redirected to after the login limit is met or surpassed.
- `ilr_error_text` - The error text that is displayed back to the user on the redirection landing page. (Note: Will only display messages above `wp_login_form()`)
- `ilr_error_text_color` - The left border of the message displayed on redirection.

**Other Filters**
- `ilr_reset_user_key`
- `ilr_redirect_query_args`
- `ilr_username_column_actions`
- `ilr_ip_address_column_actions`
- `ilr_log_table_limit`
- `ilr_sanitize_options`
- `ilr_invalid_login_redirect`

**Transient**
- `ilr_transient_duration` - The length of time that the login attempt is stored for each user. By default, the login transients are stored in the database for 1 hour.

#### Developer Mode

To enable developer mode, you can create a `mu-plugin` that defines the `INVALID_LOGIN_REDIRECT_DEVELOPER` constant as true. This can also be added to the current themes **functions.php** file to enable developer mode.

Developer mode should be used during development of additional add-ons, or for testing purposes.

**Example:**
```php
define( 'INVALID_LOGIN_REDIRECT_DEVELOPER', true );
```

You can confirm that developer mode is enabled by viewing the Invalid Login Redirect settings page inside of 'Tools > Invalid Login Redirect'. In the header, you should see a badge visible next to the plugin version that says 'Developer'.

#### Actions

`ilr_invalid_login`

Fires when an invalid login is detected.

**Parameters:**
- `$username`  - The username entered when the invalid login was triggered.
- `$error_obj` - The error object containing the error codes.
