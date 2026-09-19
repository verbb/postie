import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedPostieFixture } from '../../support/fixtures';

let route = '/admin/postie/providers';

export default defineScreenshotScenario({
    id: 'postie-feature-tour-providers',
    output: 'feature-tour/providers.png',
    route: () => route,
    viewport: { width: 1280, height: 800, deviceScaleFactor: 2 },
    async setup(context) {
        route = (await seedPostieFixture(context)).providersRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '#providers-vue-admin-table table', state: 'visible' },
        { type: 'text', text: 'Australia Post' },
        { type: 'text', text: 'FedEx' },
    ],
    target: { type: 'selector', selector: '#providers-vue-admin-table', padding: 10 },
    caption: 'Several real Postie provider records configured together in Craft 5.',
    intent: 'Show that a store can configure and order multiple carriers from one Postie screen.',
});
