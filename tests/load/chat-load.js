/**
 * LCHAT-78: k6 Load Test Script — 50 Concurrent Visitors
 *
 * Usage:
 *   k6 run tests/load/chat-load.js
 *
 * Environment variables:
 *   BASE_URL  — Application URL (default: http://localhost:8098)
 *   API_KEY   — Widget API key (default: test-api-key)
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Trend } from 'k6/metrics';

// ─── Custom Metrics ───────────────────────────────────────────────
const roomsCreated = new Counter('lchat_rooms_created');
const messagesSent = new Counter('lchat_messages_sent');
const roomCreateDuration = new Trend('lchat_room_create_duration');
const messageSendDuration = new Trend('lchat_message_send_duration');

// ─── Configuration ────────────────────────────────────────────────
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8098';
const API_KEY = __ENV.API_KEY || 'test-api-key';

export const options = {
  scenarios: {
    visitors: {
      executor: 'constant-vus',
      vus: 50,
      duration: '2m',
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<2000'],    // 95% of requests < 2s
    http_req_failed: ['rate<0.05'],       // Error rate < 5%
    lchat_room_create_duration: ['p(95)<3000'],
    lchat_message_send_duration: ['p(95)<1500'],
  },
};

const headers = {
  'Content-Type': 'application/json',
  'X-API-Key': API_KEY,
};

export default function () {
  const vuId = __VU;
  const iter = __ITER;
  const visitorId = `load-visitor-${vuId}-${iter}-${Date.now()}`;
  const visitorName = `Load Tester ${vuId}`;

  // Step 1: Create a chat room
  const createPayload = JSON.stringify({
    visitor_id: visitorId,
    visitor_name: visitorName,
  });

  const createRes = http.post(`${BASE_URL}/api/rooms`, createPayload, { headers });
  roomCreateDuration.add(createRes.timings.duration);

  const createOk = check(createRes, {
    'room created (201)': (r) => r.status === 201,
    'room has id': (r) => {
      try {
        return JSON.parse(r.body).data.id !== undefined;
      } catch {
        return false;
      }
    },
  });

  if (!createOk) {
    console.warn(`[VU ${vuId}] Room creation failed: ${createRes.status} ${createRes.body}`);
    sleep(1);
    return;
  }

  const roomId = JSON.parse(createRes.body).data.id;
  roomsCreated.add(1);

  sleep(0.5); // Small pause between room creation and messaging

  // Step 2: Send multiple messages
  const messageCount = 3;
  for (let i = 0; i < messageCount; i++) {
    const msgPayload = JSON.stringify({
      sender_type: 'visitor',
      sender_name: visitorName,
      content: `Load test message ${i + 1} from VU ${vuId}`,
      content_type: 'text',
    });

    const msgRes = http.post(`${BASE_URL}/api/rooms/${roomId}/messages`, msgPayload, { headers });
    messageSendDuration.add(msgRes.timings.duration);

    check(msgRes, {
      'message sent (201)': (r) => r.status === 201,
    });

    messagesSent.add(1);
    sleep(0.3); // Simulate typing delay
  }

  // Step 3: Fetch messages (simulate polling)
  const fetchRes = http.get(`${BASE_URL}/api/rooms/${roomId}/messages`, { headers });
  check(fetchRes, {
    'messages fetched (200)': (r) => r.status === 200,
    'messages returned': (r) => {
      try {
        return JSON.parse(r.body).data.length >= messageCount;
      } catch {
        return false;
      }
    },
  });

  sleep(1); // Pause before next iteration
}

export function handleSummary(data) {
  const summary = {
    'Total rooms created': data.metrics.lchat_rooms_created?.values?.count || 0,
    'Total messages sent': data.metrics.lchat_messages_sent?.values?.count || 0,
    'Avg room create (ms)': Math.round(data.metrics.lchat_room_create_duration?.values?.avg || 0),
    'p95 room create (ms)': Math.round(data.metrics.lchat_room_create_duration?.values?.['p(95)'] || 0),
    'Avg message send (ms)': Math.round(data.metrics.lchat_message_send_duration?.values?.avg || 0),
    'p95 message send (ms)': Math.round(data.metrics.lchat_message_send_duration?.values?.['p(95)'] || 0),
    'HTTP req failed rate': data.metrics.http_req_failed?.values?.rate || 0,
  };

  console.log('\n=== LCHAT Load Test Summary ===');
  for (const [key, val] of Object.entries(summary)) {
    console.log(`  ${key}: ${val}`);
  }
  console.log('===============================\n');

  return {
    stdout: JSON.stringify(summary, null, 2),
  };
}
