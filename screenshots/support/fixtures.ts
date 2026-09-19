import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

type PostieFixture = {
    providerRoute: string;
    providersRoute: string;
    storeSetupRoute: string;
    orderRoute: string;
};

const supportDir = dirname(fileURLToPath(import.meta.url));
const seedScript = readFileSync(join(supportDir, 'seed', 'seed-postie.php'), 'utf8');

/** Seed genuine Commerce, Postie provider and fulfilment records. */
export async function seedPostieFixture(context: ScreenshotSetupContext): Promise<PostieFixture> {
    const output = await context.runCraftScript(seedScript, { label: 'seed-postie-feature-tour' });
    const fixture = JSON.parse(output.trim()) as PostieFixture;

    if (!fixture.providerRoute || !fixture.providersRoute || !fixture.storeSetupRoute || !fixture.orderRoute) {
        throw new Error(`Invalid Postie fixture payload: ${output}`);
    }

    return fixture;
}
