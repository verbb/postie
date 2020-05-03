# Manually Fetching Rates

As soon as your cart has a valid address, Commerce will try to provide shipping methods and rules for the customer to choose. This might be useful for showing live rates on the product or cart pages. However, in the case of Postie, this means that requests to each enabled providers API's are fired - potentially unnecessarily.

Because of this, you may notice adding to cart, or anything interacting with your cart is slow. While Postie does feature caching of returned rates from providers - so long as your address and cart contents haven't changed - this does not improve when adding items to a cart for example.

To get around this issue you can opt to fetch live rates only when you require. To do this, you can set the config option `manualFetchRates` to `true`. You'll also need to use (or change) the value in `fetchRatesPostValue` a POST request to actually fetch the rates. Refer to the [configuration](docs:get-started/configuration) options.

For example, you might have a button during checkout labelled "Fetch Shipping Rates", with the following code:

```twig
<form method="POST">
    <input type="hidden" name="action" value="commerce/cart/update-cart">
    {{ csrfInput() }}

    {# Enter the value as per your config setting #}
    <input type="hidden" name="fetchRatesPostValue" value="postie-fetch-rates">

    {# the rest of your form #}

    <input type="submit" value="Fetch Shipping Rates">
</form>
```

Submitting this form will fetch live rates, and reload the page. You could also do a similar solution with Ajax. It may also be benefitial to include this hidden input on the previous step in checkout, so that shipping methods and rates are availalable on page-load.

For example, you might have an "Addresses" step in checkout, with the form:

```twig
<form method="POST">
    <input type="hidden" name="action" value="commerce/cart/update-cart">
    {{ redirectInput('shop/checkout/shipping') }}
    {{ csrfInput() }}

    {# Enter the value as per your config setting #}
    <input type="hidden" name="fetchRatesPostValue" value="postie-fetch-rates">

    {# the rest of your form #}

    <input type="submit" value="Update Address and Proceed">
</form>
```

Once this is submitted, it will redirect you to `shop/checkout/shipping` (or whatever your provided URL), and because you're setting `fetchRatesPostValue`, it will fetch the live rates, ready for the shipping template.