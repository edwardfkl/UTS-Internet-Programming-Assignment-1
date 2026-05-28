import { beforeEach, describe, expect, it } from "vitest";
import {
  clearAuthToken,
  getAuthToken,
  setAuthToken,
} from "@/lib/authToken";

describe("authToken storage", () => {
  beforeEach(() => {
    window.localStorage.clear();
  });

  it("returns null when no token is stored", () => {
    expect(getAuthToken()).toBeNull();
  });

  it("persists and reads the bearer token", () => {
    setAuthToken("jwt-abc");
    expect(getAuthToken()).toBe("jwt-abc");
    clearAuthToken();
    expect(getAuthToken()).toBeNull();
  });

  it("overwrites an existing token", () => {
    setAuthToken("first-token");
    setAuthToken("second-token");
    expect(getAuthToken()).toBe("second-token");
  });

  it("clearAuthToken is safe when nothing is stored", () => {
    expect(() => clearAuthToken()).not.toThrow();
    expect(getAuthToken()).toBeNull();
  });
});
