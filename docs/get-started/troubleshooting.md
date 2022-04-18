# Troubleshooting

## Postage options aren't up to date
Because Postie uses caching for data from providers, this data can potentially cause issues by referring to old, outdated data. In this case, clear Craft's caches by going to _Settings → Clear Caches_.

## Postage options aren't showing despite having everything setup
Postie sends the total dimensions of every single line item in the cart to the provider APIs to calculate the total shipping costs based on the dimensions. Make sure you have set up dimensions for every product.

Also check you are using the right **Weight Unit** and **Dimension Unit** in _Commerce → Settings_.

## The shipping page of checkout is very slow, or throws a 500 error
This can happen when trying to request too many provider services in one go, often on a server with limited hardware resources. We'd recommend the server has adequate resources and memory limits. Decreasing the number of providers to fetch will also speed this process up, so ensure you enable only the services you require.

## There's some other error going on, or its not working
Fortunately, Postie logs all errors and API responses to a log file under `craft/storage/logs/postie.log`. If you're having issues, see if there is anything in this log file that points to the issue, or [get in contact](/contact).