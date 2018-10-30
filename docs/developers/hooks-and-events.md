# Hooks Reference

Postie provides a single hook, allowing you to create your own shipping providers.

### registerPostieProvider

```php
public function registerPostieProviders()
{
    return array(
        new MyFirstShippingProvider(),
        new MySecondShippingProvider(),
    );
}
```

The hook must return an array of **Shipping Provider Classes**. See [Creating Your Own Shipping Provider](/craft-plugins/postie/docs/developer/shipping-provider) for more details.