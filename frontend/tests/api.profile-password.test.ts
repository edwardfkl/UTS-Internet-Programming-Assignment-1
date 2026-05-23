import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { changePassword } from "@/lib/passwordApi";
import { fetchProfile, updateProfile } from "@/lib/profileApi";
import { setAuthToken, clearAuthToken } from "@/lib/authToken";
import {
  headerValue,
  installFetchMock,
  jsonResponse,
  TEST_API_BASE,
} from "./helpers/fetchMock";

const profile = {
  id: 1,
  name: "Ada",
  email: "ada@example.com",
  avatar_url: null,
  phone: null,
  shipping_recipient_name: null,
  shipping_line1: null,
  shipping_line2: null,
  shipping_city: null,
  shipping_state: null,
  shipping_postcode: null,
  shipping_country: null,
};

describe("profileApi", () => {
  beforeEach(() => {
    window.localStorage.clear();
    vi.stubEnv("NEXT_PUBLIC_API_URL", TEST_API_BASE);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("fetchProfile requires sign-in", async () => {
    await expect(fetchProfile()).rejects.toThrow("Not signed in");
  });

  it("fetchProfile loads profile for authenticated user", async () => {
    setAuthToken("jwt-p");
    installFetchMock((url, init) => {
      const method = init?.method ?? "GET";
      if (url.endsWith("/api/profile") && method === "GET") {
        return jsonResponse(profile);
      }
      return jsonResponse({}, 404);
    });
    await expect(fetchProfile()).resolves.toEqual(profile);
    clearAuthToken();
  });

  it("updateProfile PATCHes partial fields", async () => {
    setAuthToken("jwt-p");
    const fetchMock = installFetchMock((url, init) => {
      if (url.endsWith("/api/profile") && init?.method === "PATCH") {
        expect(JSON.parse(String(init.body))).toEqual({ name: "Ada L." });
        return jsonResponse({ ...profile, name: "Ada L." });
      }
      return jsonResponse({}, 404);
    });
    const updated = await updateProfile({ name: "Ada L." });
    expect(updated.name).toBe("Ada L.");
    expect(fetchMock).toHaveBeenCalledOnce();
    clearAuthToken();
  });

  it("updateProfile surfaces validation errors", async () => {
    setAuthToken("jwt-p");
    installFetchMock(() =>
      jsonResponse(
        { errors: { phone: ["The phone field is required."] } },
        422,
      ),
    );
    await expect(updateProfile({ phone: "" })).rejects.toThrow(
      "The phone field is required.",
    );
    clearAuthToken();
  });
});

describe("passwordApi", () => {
  beforeEach(() => {
    window.localStorage.clear();
    vi.stubEnv("NEXT_PUBLIC_API_URL", TEST_API_BASE);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("changePassword requires sign-in", async () => {
    await expect(
      changePassword({
        currentPassword: "old",
        password: "new",
        passwordConfirmation: "new",
      }),
    ).rejects.toThrow("Not signed in");
  });

  it("changePassword PATCHes credentials", async () => {
    setAuthToken("jwt-pw");
    const fetchMock = installFetchMock((url, init) => {
      if (url.endsWith("/api/password") && init?.method === "PATCH") {
        expect(headerValue(init, "Authorization")).toBe("Bearer jwt-pw");
        expect(JSON.parse(String(init.body))).toEqual({
          current_password: "old",
          password: "newpass",
          password_confirmation: "newpass",
        });
        return jsonResponse({});
      }
      return jsonResponse({}, 404);
    });
    await changePassword({
      currentPassword: "old",
      password: "newpass",
      passwordConfirmation: "newpass",
    });
    expect(fetchMock).toHaveBeenCalledOnce();
    clearAuthToken();
  });
});
