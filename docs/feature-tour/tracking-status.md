# Tracking Status
Given a tracking number, you can use Postie to fetch the status and summary of its journey. This can be a nice touch for customers on their order page to keep track of their shipments.

:::tip
Not all providers support fetching tracking status. Be sure to check each [Shipping Provider](docs:shipping-providers) for more details.
:::

Postie accepts a collection of tracking numbers to query against the provider of your choice.

```twig
{% set trackingStatuses = craft.postie.getTrackingStatus('australiaPost', ['7XXXXXXXXXX', 'R8XXXXXXXXXXX']) %}

{% for trackingStatus in trackingStatuses  %}
    {{ trackingStatus.trackingNumber }}: {{ trackingStatus.status }}
{% endfor %}
```

Here, we pass in an array of tracking numbers and output the result. The `trackingStatuses` variable here will contain an array of `verbb\shippy\models\Tracking` objects containing information about the tracking status.

For example, this might produce the following value for `trackingStatuses`:

```php
[
    verbb\shippy\models\Tracking: {
        carrier: verbb\shippy\carriers\AustraliaPost,
        trackingNumber: '7XXXXXXXXXX',
        status: 'delivered',
        trackingUrl: 'https://auspost.com.au/mypost/beta/track/details/7XXXXXXXXXX',
        signedBy: 'Josh Crawford',
        weight: 2,
        weightUnit: 'kg',
        details: [
            verbb\shippy\models\TrackingDetail: {
                description: 'Delivered',
                date: DateTime,
                location: 'North Melbourne, Vic',
            },
            verbb\shippy\models\TrackingDetail: {
                description: 'Onboard for delivery',
                date: DateTime,
                location: 'Sunshine West, Vic',
            },
            verbb\shippy\models\TrackingDetail: {
                description: 'Item processed at facility',
                date: DateTime,
                location: 'Melbourne, Vic',
            },
            verbb\shippy\models\TrackingDetail: {
                description: 'Received and ready for processing',
                date: DateTime,
            },
            verbb\shippy\models\TrackingDetail: {
                description: 'Shipping information received by Australia Post',
                date: DateTime,
            },
        ],
    },
    verbb\shippy\models\Tracking: {
        carrier: '(instance of `verbb\shippy\carriers\AustraliaPost`)',
        trackingNumber: 'R8XXXXXXXXXXX'
        status: 'not_found'
        errors: [
            {
                errorCode: 'ESB-10001',
                description: 'Invalid tracking ID',
            },
        ],
    }
]
```