import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedPostieFixture } from '../../support/fixtures';

let route = '/admin/commerce/orders';

export default defineScreenshotScenario({
    id: 'postie-feature-tour-shipments',
    output: 'feature-tour/shipments.png',
    route: () => route,
    viewport: { width: 1440, height: 1000, deviceScaleFactor: 2 },
    async setup(context) {
        route = (await seedPostieFixture(context)).orderRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Shipments' },
        { type: 'text', text: 'Australia Post Shipment #1' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `(() => {
                const tab = document.querySelector('#shipmentsTab');
                if (!tab) throw new Error('Unable to locate the real Postie Shipments tab.');
                tab.classList.remove('hidden');
                tab.scrollIntoView({ block: 'center' });
            })()`,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 200 } },
    ],
    target: { type: 'selector', selector: '#shipmentsTab', padding: 12 },
    caption: 'A real Commerce order with a lodged Postie shipment, tracking details and printable label.',
    intent: 'Show the fulfilment record Postie keeps alongside the order after lodging a shipment.',
});
