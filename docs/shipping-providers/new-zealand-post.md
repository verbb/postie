# New Zealand Post
In order to use New Zealand Post, you'll need to ensure you are using a valid New Zealand address as your store location. You'll also need to ensure your Craft Commerce default currency is set to NZD.

### Connect to the New Zealand Post API
1. Go to <a href="https://www.nzpost.co.nz/" target="_blank">New Zealand Post</a> and login to your account. Complete the <a href="https://www.nzpost.co.nz/user/developer-centre/register/commercial/shipping" target="_blank">commercial access form</a>.
1. Request API access for an <a href="https://www.nzpost.co.nz/business/developer-centre" target="_blank">application</a>.
1. Once access have been granted, click “add a new application”.
1. Include OAuth 2.0 grant type "Client Credentials Grant".
1. Navigate to <a href="https://anypoint.mulesoft.com/exchange/portals/nz-post-group/applications/
" target="_blank">your application</a>.
1. Copy the **Client ID** from New Zealand Post and paste in the **Client ID** field in Postie.
1. Copy the **Client Secret** from New Zealand Post and paste in the **Client Secret** field in Postie.
1. Copy the **Site Code** from New Zealand Post and paste in the **Site Code** field in Postie.

### Services
New Zealand Post doesn't offer a set list of services for you to enable or disable as required. Services are automatically returned based on the matching criteria with your shipping origin and destination.

### Configuration
Add the following code to your configuration file under the `providers` array, as per the below. Note that to disable certain services, simply omit them from the `services` array.

```php
'providers' => [
    'newZealandPost' => [
        'name' => 'New Zealand Post',
    ],
]
```