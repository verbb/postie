import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

export default defineScreenshotScenario({
    id: 'postie-docs-legacy-general-settings',
    output: 'docs/legacy/general-settings.png',
    route: '/admin/postie/settings/general',
    viewport: { width: 1280, height: 900, deviceScaleFactor: 2 },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'General Settings' },
        { type: 'text', text: 'Plugin Name' },
        { type: 'text', text: 'Shipped Order Status' },
    ],
    steps: [
        { type: 'wait', waitFor: { type: 'timeout', ms: 150 } },
    ],
    target: { type: 'selector', selector: '#main', padding: 20 },
    caption: 'Postie’s current general settings and order-status controls.',
    intent: 'Retains the legacy plugin-settings subject for future documentation while the Features page keeps its existing screenshots.',
});
