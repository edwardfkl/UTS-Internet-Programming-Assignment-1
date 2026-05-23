import { vi } from "vitest";

export const TEST_API_BASE = "http://test-api.local";

export function jsonResponse(body: unknown, status = 200): Response {
  return Response.json(body, { status });
}

export function noContentResponse(): Response {
  return new Response(null, { status: 204 });
}

export type FetchHandler = (
  url: string,
  init?: RequestInit,
) => Response | Promise<Response>;

export function installFetchMock(handler: FetchHandler) {
  const fetchMock = vi.fn((input: RequestInfo | URL, init?: RequestInit) => {
    const url = typeof input === "string" ? input : input.toString();
    return Promise.resolve(handler(url, init));
  });
  vi.stubGlobal("fetch", fetchMock);
  return fetchMock;
}

export function headerValue(
  init: RequestInit | undefined,
  name: string,
): string | undefined {
  const headers = init?.headers as Record<string, string> | undefined;
  return headers?.[name];
}
