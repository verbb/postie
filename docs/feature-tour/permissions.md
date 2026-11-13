# Permissions

Postie's permissions are for the staff who handle Commerce orders. Someone answering customer questions might only need to see an order's tracking number, while the person packing orders needs to lodge shipments and print labels. Set them in **Settings → Users → User Groups**, or on an individual user's **Permissions** tab.

**Access Postie** is listed under **General**, with Craft's other permissions for reaching each plugin's section. The other two are listed under the plugin's own heading, which shows the plugin's name in the control panel, **Postie** unless you've changed **Plugin Name** in its settings.

| Permission | What It Allows |
| --- | --- |
| Access Postie | Open Postie's **Store Setup** screen, which checks your store location and lists product variants that are missing a dimension or weight. |
| View order shipments | See the **Shipments** tab on a Commerce order, with each shipment's tracking number and tracking link, and download its labels. |
| Create order shipments | Everything **View order shipments** allows, plus lodge a new shipment for some or all of an order's items with the provider. |

The shipment permissions work on the Commerce order screen, so they also need Commerce's permission to manage orders, and someone can only work with the orders Commerce lets them view. They don't need **Access Postie**, which only covers Postie's own section. The two shipment permissions are independent, so give **Create order shipments** on its own to anyone who lodges shipments.

Admins have every permission. Providers, their shipping methods and Postie's settings are for admins only, so the **Providers** and **Settings** screens aren't available to anyone else.

Lodging a shipment creates a real consignment with the provider, and most providers charge your account each time one is lodged. Keep **Create order shipments** for the people who pack and send orders, and give **View order shipments** to anyone who only needs to look up tracking details. The [Shipments](docs:feature-tour/shipments) page covers lodging a shipment and printing its labels.
