# Displaying Rates
There's nothing you need to do to display rates from providers, which means as long as you have the provider enabled, and some shipping methods enabled, they'll appear in checkout.

:::tip
Note that shipping methods will not appear in your control panel under Commerce > Shipping. Refer to [Github issue](https://github.com/craftcms/commerce/issues/730).
:::

## Template
You might have something similar to the below in your templates. The below is take from the default Commerce templates:

```twig
{% if cart.availableShippingMethodOptions | length %}
    <form method="POST">
        <input type="hidden" name="action" value="commerce/cart/update-cart">
        {{ redirectInput('shop/checkout/payment') }}
        {{ csrfInput() }}

        {% for handle, method in cart.availableShippingMethodOptions %}
            <div class="shipping-select">
                <label>

                    <input type="radio" name="shippingMethodHandle" value="{{ handle }}" {% if handle == cart.shippingMethodHandle %}checked{% endif %} />
                    <strong>{{ method.name }}</strong>

                    <span class="price">
                        {{ method.getPrice() | commerceCurrency(cart.currency) }}
                    </span>
                </label>
            </div>
        {% endfor %}

        <span class="flash">{{ cart.getErrors('shippingMethod')|join }}</span>

        <p><input type="submit" class="button button-primary" value="Select Shipping Method"/></p>
    </form>
{% endif %}
```

Without any further alterations, rates should appear within this loop, alongside any manually-create shipping methods.

:::tip
Can't see your rates during checkout? Be sure to check the [Troubleshooting guide](docs:get-started/troubleshooting).
:::

## Rate options
When fetching rates from providers, Postie not only returns the price amount, but a few other handy options related to the rate. These can be accessed via the shipping method rate. This may be useful to find information about how long a chosen rate may take to ship, etc.

```twig
{% for handle, method in cart.availableShippingMethodOptions %}
    <div class="shipping-select">
        <label>
            <input type="radio" name="shippingMethodHandle" value="{{ handle }}" {% if handle == cart.shippingMethodHandle %}checked{% endif %} />
            <strong>{{ method.name }}</strong>

            {# Get the first shipping rule for this method #}
            {% set shippingRule = method.getShippingRules()[0] ?? [] %}

            {% if shippingRule %}
                <code>{{ dump(shippingRule.options) }}</code>
            {% endif %}

            <span class="price">
                {{ method.getPrice() | commerceCurrency(cart.currency) }}
            </span>
        </label>
    </div>
{% endfor %}
```