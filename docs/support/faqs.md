# FAQs

### Does Postie make an API request each time the shipping page is shown on checkout? [#](#does-postie-make-an-api-request-each-time-the-shipping-page-is-shown-on-checkout "Direct link to Does Postie make an API request each time the shipping page is shown on checkout?")

Fortunately no, this would be slow and unnecessary. Postie intelligently caches API requests based on the contents of your cart, and address information. If nothing apart your cart or destination address changes, then neither do the shipping calculations.

### How does Postie calculate dimensions

Postie creates only one box to fit all line items into, using a simple box packing algorithm:

- `length` - maximum length of all items
- `width` - maximum width of all items
- `height` - total height of all items

Far from perfect, this solution caters for most general cases. More intelligent and custom boxing packing algorithm solutions will be introduced into Postie soon.