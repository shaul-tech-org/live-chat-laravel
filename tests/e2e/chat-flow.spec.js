// @ts-check
import { test, expect } from '@playwright/test';

/**
 * LCHAT-77: Playwright E2E Test — Visitor <-> Admin full chat flow
 *
 * Prerequisites:
 *   - App running at BASE_URL (default http://localhost:8098)
 *   - Admin credentials in env: ADMIN_EMAIL, ADMIN_PASSWORD
 *   - A valid API key configured for the default tenant
 */

const API_KEY = process.env.LCHAT_API_KEY || 'test-api-key';
const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'password';
const VISITOR_NAME = 'E2E Visitor';
const VISITOR_MESSAGE = `Hello from visitor ${Date.now()}`;
const ADMIN_REPLY = `Admin reply ${Date.now()}`;

let roomId;

test.describe('Live Chat — Full Flow', () => {
  test.describe.configure({ mode: 'serial' });

  test('1. Visitor opens widget, creates room, and sends a message', async ({ request }) => {
    // Create room via API (simulates widget open + name entry)
    const createRes = await request.post('/api/rooms', {
      headers: { 'X-API-Key': API_KEY },
      data: {
        visitor_id: `e2e-visitor-${Date.now()}`,
        visitor_name: VISITOR_NAME,
      },
    });
    expect(createRes.ok()).toBeTruthy();

    const body = await createRes.json();
    roomId = body.data.id;
    expect(roomId).toBeTruthy();

    // Send message
    const msgRes = await request.post(`/api/rooms/${roomId}/messages`, {
      headers: { 'X-API-Key': API_KEY },
      data: {
        sender_type: 'visitor',
        sender_name: VISITOR_NAME,
        content: VISITOR_MESSAGE,
        content_type: 'text',
      },
    });
    expect(msgRes.ok()).toBeTruthy();
  });

  test('2. Admin logs in and sees the new room', async ({ page }) => {
    // Navigate to login
    await page.goto('/login');
    await page.fill('input[name="email"]', ADMIN_EMAIL);
    await page.fill('input[name="password"]', ADMIN_PASSWORD);
    await page.click('button[type="submit"]');

    // Should redirect to admin dashboard
    await page.waitForURL('**/admin**', { timeout: 15_000 });

    // Room list should contain the visitor name
    const roomList = page.locator('[data-testid="room-list"], .room-list, #room-list');
    await expect(roomList).toBeVisible({ timeout: 10_000 });

    // Verify visitor name appears somewhere on the page
    await expect(page.getByText(VISITOR_NAME)).toBeVisible({ timeout: 10_000 });
  });

  test('3. Admin opens room and sends reply', async ({ request }) => {
    // Admin login to get token
    const loginRes = await request.post('/api/auth/login', {
      data: { email: ADMIN_EMAIL, password: ADMIN_PASSWORD },
    });
    expect(loginRes.ok()).toBeTruthy();
    const loginBody = await loginRes.json();
    const token = loginBody.data?.token || loginBody.token;

    // Send admin reply
    const replyRes = await request.post(`/api/admin/rooms/${roomId}/messages`, {
      headers: { Authorization: `Bearer ${token}` },
      data: {
        content: ADMIN_REPLY,
        content_type: 'text',
      },
    });
    expect(replyRes.ok()).toBeTruthy();
  });

  test('4. Visitor receives admin reply via polling', async ({ request }) => {
    const messagesRes = await request.get(`/api/rooms/${roomId}/messages`, {
      headers: { 'X-API-Key': API_KEY },
    });
    expect(messagesRes.ok()).toBeTruthy();

    const body = await messagesRes.json();
    const messages = body.data || [];

    // Should contain both visitor message and admin reply
    const contents = messages.map((m) => m.content);
    expect(contents).toContain(VISITOR_MESSAGE);
    expect(contents).toContain(ADMIN_REPLY);
  });

  test('5. Page refresh preserves chat history', async ({ request }) => {
    // Re-fetch messages (simulates page refresh → widget reload → fetch history)
    const messagesRes = await request.get(`/api/rooms/${roomId}/messages`, {
      headers: { 'X-API-Key': API_KEY },
    });
    expect(messagesRes.ok()).toBeTruthy();

    const body = await messagesRes.json();
    const messages = body.data || [];

    expect(messages.length).toBeGreaterThanOrEqual(2);

    const contents = messages.map((m) => m.content);
    expect(contents).toContain(VISITOR_MESSAGE);
    expect(contents).toContain(ADMIN_REPLY);
  });
});
