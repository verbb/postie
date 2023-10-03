# Shipments
Postie can create shipments for Commerce orders which are lodged with the respective provider APIs. Once lodged, a tracking number and label is returned and stored alongside the order. With this information, your shop owners can print the label to start the shipment process, and notify your customers of the order being shipped with the tracking number.

:::tip
Not all providers support shipments. Be sure to check each [Shipping Provider](docs:shipping-providers) for more details.
:::

:::warning
Be aware that most — if not all — providers charge your account each time a shipment is lodged. Be mindful when testing to set the "Is Production" value as appropriate.
:::

## Shipment Summary
The ability to add a shipment will be available in the "Shipments" tab of any Commerce order. If the chosen shipping method is a Postie-provided one, you'll be able to create a shipment with the provider the user has chosen, and for their selected service.

Orders can have multiple shipments, and can also partially ship order contents if multiple shipments are required for certain items.

Once created, a list of shipments will be shown against the order with the tracking number, tracking URL and the ability to download labels to be printed.

## Partial Shipments
When creating a shipmeny, you can select the number of line items and quantity you wish to ship. This will create a shipment via the provider API and record a postage label and tracking number.

Due to this, you're also able to partially ship items by selecting certain quantities or line items. 

## Order Statuses
Postie will create a **Shipped** and **Partially Shipped** order status (if they don't already exist) which will be used to change the order to when creating shipments. When a shipment is created, if only partially fulfilled, the order will be marked as **Partially Shipped**. If all items in the order have been fulfilled, then the order status will change to **Shipped**.

It might be a good idea to connect a Commerce email to this change in status to notify your customers that an item has been shipped. You can include the following in such an email:

```twig
{% set shipments = craft.postie.getShipmentsForOrder(order) %}

{% for shipment in shipments %}
    {{ shipment.trackingNumber }}
{% endfor %}
```