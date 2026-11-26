<?php
/**
 * Automated Verification for Slideshow Empty-to-Populated State Machine
 *
 * Simulates SlideshowApp state transitions and executes JavaScript verification via Node.js
 */

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.save_path', sys_get_temp_dir());
}

echo "=== RUNNING SLIDESHOW TRANSITION TEST SUITE ===\n\n";

$jsTestFile = __DIR__ . '/test_slideshow_logic.cjs';

$jsTestCode = <<<'JS'
const fs = require('fs');
const assert = require('assert');

// Mock DOM elements
class MockClassList {
    constructor() { this.classes = new Set(); }
    add(cls) { this.classes.add(cls); }
    remove(cls) { this.classes.delete(cls); }
    contains(cls) { return this.classes.has(cls); }
}

class MockElement {
    constructor(id) {
        this.id = id;
        this.classList = new MockClassList();
        this.style = {};
        this.src = '';
        this.innerText = '';
        this.innerHTML = '';
        this.title = '';
    }
    addEventListener() {}
}

// Global Image mock
global.Image = class {
    constructor() {
        this._src = '';
        this.onload = null;
        this.onerror = null;
    }
    set src(val) {
        this._src = val;
        setTimeout(() => { if (this.onload) this.onload(); }, 1);
    }
    get src() { return this._src; }
};

const fullScript = fs.readFileSync('assets/js/slideshow.js', 'utf8');

// Read SlideshowApp code and append export
const scriptToEval = fullScript.replace(/document\.addEventListener\('DOMContentLoaded'[\s\S]*?\);?\s*$/, '')
    + '\n; globalThis.SlideshowApp = SlideshowApp;';

const vm = require('vm');
let currentFetchMock = async () => ({
    ok: true,
    json: async () => ({ success: true, interval: 5, photos: [] })
});

const context = {
    document: {
        getElementById: (id) => new MockElement(id),
        addEventListener: () => {},
        documentElement: {}
    },
    fetch: (...args) => currentFetchMock(...args),
    setTimeout: setTimeout,
    clearTimeout: clearTimeout,
    setInterval: () => {}, // prevent open event loop timers in node
    clearInterval: clearInterval,
    console: console,
    Image: global.Image
};
vm.createContext(context);
vm.runInContext(scriptToEval, context);
const SlideshowApp = context.SlideshowApp;

async function runTests() {
    console.log("[TEST 1] Initial empty queue correctly displays empty state");
    {
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 5, photos: [] })
        });
        const app = new SlideshowApp();
        await app.init();
        assert.strictEqual(app.photos.length, 0, "Photos queue should be empty");
        assert.strictEqual(app.emptyState.classList.contains('d-none'), false, "emptyState should not have d-none");
        assert.strictEqual(app.timer, null, "Timer should be null when queue is empty");
        assert.strictEqual(app.slideCounter.innerText, '0 / 0', "Counter should show 0 / 0");
        console.log("  -> PASSED: Empty queue properly initialized with timer=null and empty state visible.");
    }

    console.log("\n[TEST 2] Background poll receives first photo -> transitions from empty to populated");
    {
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 5, photos: [] })
        });
        const app = new SlideshowApp();
        await app.init();

        const singlePhoto = [{ id: 101, url: 'uploads/photo1.jpg', caption: 'Welcome Visitor' }];
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 5, photos: singlePhoto })
        });

        await app.fetchPhotos(true);

        assert.strictEqual(app.photos.length, 1, "Queue should now have 1 photo");
        assert.strictEqual(app.emptyState.classList.contains('d-none'), true, "emptyState should be hidden with d-none");
        assert.strictEqual(app.currentIndex, 0, "currentIndex should be 0");
        assert.strictEqual(app.slideCounter.innerText, '1 / 1', "Counter should show 1 / 1");
        assert.strictEqual(app.visitorBadge.classList.contains('d-none'), false, "Visitor badge should be visible");
        assert.strictEqual(app.timer, null, "Rotation timer should not run for single photo");
        console.log("  -> PASSED: Transition from 0 to 1 photo correctly renders slide and hides empty state.");
    }

    console.log("\n[TEST 3] Background poll receives multiple photos from empty -> renders slide(0) & starts rotation");
    {
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 4, photos: [] })
        });
        const app = new SlideshowApp();
        await app.init();

        const twoPhotos = [
            { id: 201, url: 'uploads/photo1.jpg', caption: 'Photo 1' },
            { id: 202, url: 'uploads/photo2.jpg', caption: 'Photo 2' }
        ];
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 4, photos: twoPhotos })
        });

        await app.fetchPhotos(true);

        assert.strictEqual(app.photos.length, 2, "Queue should have 2 photos");
        assert.strictEqual(app.emptyState.classList.contains('d-none'), true, "emptyState must be hidden");
        assert.strictEqual(app.currentIndex, 0, "First slide should be active");
        assert.strictEqual(app.slideCounter.innerText, '1 / 2', "Counter should show 1 / 2");
        assert.notStrictEqual(app.timer, null, "Rotation timer must be actively scheduled");

        // Verify deduplication: another background poll with same photos must not create multiple running timers
        await app.fetchPhotos(true);
        assert.strictEqual(app.photos.length, 2, "Queue remains 2 photos");
        assert.strictEqual(app.currentIndex, 0, "Active slide preserved at 0");
        app.stopTimer();
        console.log("  -> PASSED: Transition from 0 to 2 photos renders slide 0 and schedules rotation timer.");
    }

    console.log("\n[TEST 4] Transition from 1 photo to multiple photos initiates rotation timer");
    {
        const singlePhoto = [{ id: 301, url: 'uploads/single.jpg', caption: 'Solo' }];
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 5, photos: singlePhoto })
        });
        const app = new SlideshowApp();
        await app.init();

        assert.strictEqual(app.photos.length, 1);
        assert.strictEqual(app.timer, null, "No timer for single photo");

        // Background poll arrives with second photo
        const multiPhotos = [
            { id: 301, url: 'uploads/single.jpg', caption: 'Solo' },
            { id: 302, url: 'uploads/second.jpg', caption: 'Second' }
        ];
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 5, photos: multiPhotos })
        });
        await app.fetchPhotos(true);

        assert.strictEqual(app.photos.length, 2);
        assert.strictEqual(app.currentIndex, 0, "Index 0 preserved");
        assert.strictEqual(app.slideCounter.innerText, '1 / 2', "Counter updated to 1 / 2");
        assert.notStrictEqual(app.timer, null, "Rotation timer should now be running");
        app.stopTimer();
        console.log("  -> PASSED: Expanding from 1 to 2 photos kicks off rotation timer automatically.");
    }

    console.log("\n[TEST 5] Unapproved / deleted active photo gracefully falls back without crashing");
    {
        const initialPhotos = [
            { id: 401, url: 'uploads/p1.jpg', caption: 'P1' },
            { id: 402, url: 'uploads/p2.jpg', caption: 'P2' }
        ];
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 5, photos: initialPhotos })
        });
        const app = new SlideshowApp();
        await app.init();
        app.currentIndex = 1; // Currently viewing P2

        // Moderation unapproves / deletes P2
        const updatedPhotos = [
            { id: 401, url: 'uploads/p1.jpg', caption: 'P1' }
        ];
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 5, photos: updatedPhotos })
        });
        await app.fetchPhotos(true);

        assert.strictEqual(app.photos.length, 1);
        assert.strictEqual(app.currentIndex, 0, "Fallback to slide 0 since P2 was removed and length is 1");
        assert.strictEqual(app.slideCounter.innerText, '1 / 1');
        console.log("  -> PASSED: Active photo removal correctly falls back to valid slide index.");
    }

    console.log("\n[TEST 6] Transition from populated queue to empty queue (all unapproved)");
    {
        const initialPhotos = [{ id: 501, url: 'uploads/p1.jpg', caption: 'P1' }];
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 5, photos: initialPhotos })
        });
        const app = new SlideshowApp();
        await app.init();

        // All photos unapproved
        currentFetchMock = async () => ({
            ok: true,
            json: async () => ({ success: true, interval: 5, photos: [] })
        });
        await app.fetchPhotos(true);

        assert.strictEqual(app.photos.length, 0);
        assert.strictEqual(app.emptyState.classList.contains('d-none'), false, "emptyState must be displayed");
        assert.strictEqual(app.timer, null, "Timer must be stopped");
        assert.strictEqual(app.slideCounter.innerText, '0 / 0');
        console.log("  -> PASSED: Transition from populated to 0 photos restores empty state and stops timer.");
    }

    console.log("\nALL SLIDESHOW TRANSITION TESTS PASSED SUCCESSFULLY!\n");
    process.exit(0);
}

runTests().catch(err => {
    console.error("Test execution failed:", err);
    process.exit(1);
});
JS;

file_put_contents($jsTestFile, $jsTestCode);

// Execute via Node
$command = "node " . escapeshellarg($jsTestFile) . " 2>&1";
exec($command, $output, $returnCode);

echo implode("\n", $output) . "\n";

// Cleanup temp script
if (file_exists($jsTestFile)) {
    unlink($jsTestFile);
}

if ($returnCode === 0) {
    echo "[SUCCESS] Slideshow transition test suite passed.\n";
    exit(0);
} else {
    echo "[FAIL] Slideshow transition test suite failed (code: $returnCode).\n";
    exit(1);
}
