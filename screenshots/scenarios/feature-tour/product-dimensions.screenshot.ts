import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedPostieFixture } from '../../support/fixtures';

let route = '/admin/postie/store-setup';

export default defineScreenshotScenario({
    id: 'postie-feature-tour-product-dimensions',
    output: 'feature-tour/product-dimensions.png',
    route: () => route,
    viewport: { width: 1280, height: 900, deviceScaleFactor: 2 },
    async setup(context) {
        route = (await seedPostieFixture(context)).storeSetupRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Products' },
        { type: 'text', text: 'Harbour linen throw' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `(() => {
                const heading = [...document.querySelectorAll('h2')].find((item) => item.textContent?.trim() === 'Products');
                const table = heading?.parentElement?.querySelector('table');
                if (!heading || !table) throw new Error('Unable to locate Postie product helper.');
                const wrapper = document.createElement('section');
                wrapper.id = 'postie-products-screenshot';
                heading.parentElement?.insertBefore(wrapper, heading);
                wrapper.append(heading, heading.nextElementSibling, table);
            })()`,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 100 } },
    ],
    target: { type: 'selector', selector: '#postie-products-screenshot', padding: 18 },
    caption: 'The current Postie helper identifying a shippable variant with missing dimensions.',
    intent: 'Show how Postie catches incomplete product measurements before they affect live quotes.',
});
