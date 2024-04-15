# Displaying Rates
Postie hooks into Commerce's `ShippingMethods::EVENT_REGISTER_AVAILABLE_SHIPPING_METHODS` event, ensuring that the rates fetched from providers become Shipping Methods in Commerce that you might be familiar with.

This means you can loop through all available shipping methods for an order with the default Commerce template. Here's an example that outputs `label` elements for your Commerce shipping methods. This will include any Commerce-defined ones and any Postie-provided ones.

```twig
{% for handle, method in cart.availableShippingMethodOptions %}
    <div class="shipping-select">
        <label>
            <input type="radio" name="shippingMethodHandle" value="{{ handle }}" {% if handle == cart.shippingMethodHandle %}checked{% endif %} />
            <strong>{{ method.name }}</strong>

            {{ method.getPrice() | commerceCurrency(cart.currency) }}
        </label>
    </div>
{% endfor %}
```

## Route Check
One caveat for this approach is that as soon as your cart has a valid address, Commerce will try to provide shipping methods and rules for the customer to choose from. What this means for Postie is that this will trigger a request to fetch rates from a provider. This can be from **any** page on your site — which is certainly not ideal!

This can happen if the user is logged in (their address is associated with the current cart), or the user aborts checkout once they have entered a valid address (they may want to explore your shop more). In either scenario, Commerce will tell Postie to fetch live rates.

Instead, we only trigger requests to fetch rates based on the current route. Paired with intelligent caching, we save a lot of requests to providers for fetching rates.

By default, Postie will only fetch rates when on the following routes:
- `/{cpTrigger}/commerce/orders/\d+`
- `/shop/checkout/shipping`

For this reason, if you have a different URL for your shipping page in your checkout, you'll want to adjust the [configuration](docs:get-started/configuration) settings to include your shipping page. Otherwise, rates won't appear!