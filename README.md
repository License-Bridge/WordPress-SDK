# WordPress-SDK

This is official License Bridge WordPress SDK. Adding this SDK to your WordPress plugin you enables all features that comes with License Bridge platform.

- Licensing for WordPress plugin
- Landing page with Secure Checkout for customers to purchase a license. After purchase plugin will be auto updated with premium version.
- Recurring Payments - Build a sustainable business with recurring payments. Sell annual or monthly subscriptions.
- Automatic Updates for WordPress plugins & themes

## Integrate SDK with WordPress plugin

This is an example how you can create your own unique method that will be used in your plugin only, and your global variable that will hold SDK.

Make sure to replace `my_license` with your own referrence.

```
if (!function_exists('my_license')) {
    // Create a helper function for easy SDK access.
    function my_license()
    {
        global $my_license;
        if ($my_license) {
            return $my_license;
        }

        include __DIR__ . '/vendor/license-bridge/wordpress-sdk/src/Boot/bootstrap.php';
        
        $pluginFilePath = __FILE__;
        $my_license = Loader::register($pluginFilePath, [
            'plugin-slug'                   => plugin_basename(__FILE__),
            'license-product-slug'          => 'my-first-product',
        ]);

        return $my_license;
    }
    my_license();
}
```

- **plugin-slug** is your plugin slug usualy created like this: `plugin_basename(__FILE__)`
- **license-product-slug** is a slug that represent your product/plugin/theme on License Bridge platform. **LINK To HELP FILE**

### Usage example

To access to the SDK you can use global variable you created for your own plugin.

```
$my_license
```

or by calling the custom method that will return SDK without creating it each time.
```
my_license()
```

### Get unique landing page URL for your product

```
$link = $my_license->purchase_link($plugin_slug);
// https://licensebridge.com/market/my-plugin
```

### Check if user have license key

```
if ($my_license->license_exists($plugin_slug)) {
    // User have the license
}
```

### Check is the license active or not
```
if ($my_license->is_license_active($plugin_slug)) {
    // User license is active
}
```

### Get license details from the License Bridge API

```
$response = $my_license->license($plugin_slug);
```

Response can be false is license do not exist. Expected response is array with license details:

```
array (size=13)
  'first_name' => string 'John' (length=11)
  'last_name' => string 'Doe' (length=10)
  'full_name' => string 'John Doe' (length=22)
  'email' => string 'johndoe@mail.com' (length=20)
  'plan_type' => string 'month' (length=5)
  'plan_name' => string 'plan' (length=4)
  'charge_type' => string 'subscription' (length=12)
  'gateway' => string 'stripe' (length=6)
  'active' => boolean true
  'created_at' => string '01/27/2022 18:07:48' (length=19)
  'subscribed' => boolean true
  'cancelled' => boolean true
  'subscription' => 
    array (size=4)
      'stripe_id' => string 'sub_0KMcNuxCqoZozrbaG75rgcZs' (length=28)
      'stripe_customer_id' => string 'cus_L2hnYEuIw2p3pE' (length=18)
      'ends_at_formated' => string 'February 27, 2022' (length=17)
      'is_ended' => boolean false
```

### Cancel the user license request

In some cases when user is subscribed to your plugin you can allow a user to cancel subscription to your plugin.
This is how user can cancel license subscription.

```
if ($my_license->cancel_license($plugin_slug)) {
    // User license is canceled
}
```

### License
Copyright (c) License Bridge.

Licensed under the GNU general public license (version 3).