# Providers
A **Provider** is a shipping provider used for shipping your packages. Depending on your shipping needs will determine which service you'd like to use, and currently, Postie supports the following providers:

- [Aramex →](docs:shipping-providers/aramex)
- [Aramex Australia →](docs:shipping-providers/aramex-australia)
- [Australia Post →](docs:shipping-providers/australia-post)
- [Bring →](docs:shipping-providers/bring)
- [Canada Post →](docs:shipping-providers/canada-post)
- [Colissimo →](docs:shipping-providers/colissimo)
- [DHL Express →](docs:shipping-providers/dhl-express)
- [Fastway →](docs:shipping-providers/fastway)
- [FedEx →](docs:shipping-providers/fed-ex)
- [FedEx Freight →](docs:shipping-providers/fed-ex-freight)
- [Interparcel →](docs:shipping-providers/interparcel)
- [New Zealand Post →](docs:shipping-providers/new-zealand-post)
- [PostNL →](docs:shipping-providers/post-nl)
- [Royal Mail →](docs:shipping-providers/royal-mail)
- [Sendle →](docs:shipping-providers/sendle)
- [TNT Australia →](docs:shipping-providers/tnt-australia)
- [UPS →](docs:shipping-providers/ups)
- [UPS Freight →](docs:shipping-providers/ups-freight)
- [USPS →](docs:shipping-providers/usps)

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact), and we'd love to add your provider to Postie.
:::

Each provider has the following settings available to configure.

## API Settings
Every provider will require different API settings, so this section will change depending on the individual provider. See each [Shipping Provider](docs:feature-tour/providers) for more details.

## Markup
You can set up a markup for every provider, which is useful to cover packing costs or other incidental costs. You can select this value to be either a **Percentage** or **Value**.

- **Percentage** — Add a markup based on the total order price. Add a markup rate between 1 and 100 to calculate a percentage.
- **Value** — Add just a pure value on top of the total order price. For example, "5" for $5.

## Check a Quote Through Fulfilment

Choose a provider and follow its account and credential instructions before building the rate display. Use its test environment when available. Enter the shipping origin and make sure your test purchasable has the weight and dimensions your provider needs; box packing and address information affect the quote.

With a working Commerce checkout, add that item to a cart and enter a destination the provider serves. Follow [Displaying Rates](docs:feature-tour/displaying-rates) to select a returned service and complete a test order. Check that its shipping method and amount match the selected quote.

If the provider supports [shipments](docs:feature-tour/shipments), open the order's Shipments tab and create a shipment for the intended quantities. Check its label, tracking number and order status. A quoted rate does not itself lodge a shipment. Inspect errors before retrying, and check the provider account when a lodging request's result is uncertain so you do not create a second shipment accidentally.
