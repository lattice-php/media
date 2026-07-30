import { act, renderHook, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useMediaUpload } from "./use-media-upload";

const apiFetch = vi.hoisted(() => vi.fn<() => Promise<Response>>());

vi.mock("@lattice-php/lattice/core/api", () => ({ apiFetch }));

const reloadEffects = JSON.stringify({
  effects: [{ type: "reload-component", props: { component: "media.library" } }],
});

class FakeRequest {
  static instances: FakeRequest[] = [];

  body: unknown = null;

  headers: Record<string, string> = {};

  method = "";

  onerror: (() => void) | null = null;

  onload: (() => void) | null = null;

  responseText = "";

  status = 0;

  upload: {
    onprogress:
      | ((event: { lengthComputable: boolean; loaded: number; total: number }) => void)
      | null;
  } = { onprogress: null };

  url = "";

  constructor() {
    FakeRequest.instances.push(this);
  }

  open(method: string, url: string): void {
    this.method = method;
    this.url = url;
  }

  setRequestHeader(key: string, value: string): void {
    this.headers[key] = value;
  }

  /** Reports progress but stays in flight, so the uploading state is observable. */
  send(body: unknown): void {
    this.body = body;
    this.upload.onprogress?.({ lengthComputable: true, loaded: 3, total: 10 });
  }

  finish(status = 200, responseText = reloadEffects): void {
    this.status = status;
    this.responseText = responseText;
    this.onload?.();
  }
}

function file(name: string): File {
  return new File(["bytes"], name, { type: "image/jpeg" });
}

function renderUpload(signed = false) {
  return renderHook(() =>
    useMediaUpload({ endpoint: "/lattice/actions/media-upload", ref: "ref-1", signed }),
  );
}

describe("useMediaUpload", () => {
  beforeEach(() => {
    document.cookie = "XSRF-TOKEN=csrf-token";
    apiFetch.mockReset();
    FakeRequest.instances = [];
    vi.stubGlobal("XMLHttpRequest", FakeRequest);
  });

  it("posts the whole batch as multipart form data and reports progress", async () => {
    const { result } = renderUpload();
    const [first, second] = [file("alpha.jpg"), file("beta.jpg")];

    act(() => result.current.addFiles([first, second]));

    await waitFor(() => expect(FakeRequest.instances).toHaveLength(1));

    const request = FakeRequest.instances[0];

    expect(request.method).toBe("POST");
    expect(request.url).toBe("/lattice/actions/media-upload");
    expect(request.headers).toMatchObject({
      Accept: "application/json",
      "X-Lattice-Ref": "ref-1",
      "X-XSRF-TOKEN": "csrf-token",
    });
    expect(request.headers["Content-Type"]).toBeUndefined();
    expect((request.body as FormData).getAll("files[]")).toEqual([first, second]);

    await waitFor(() => {
      expect(result.current.uploads).toEqual([
        expect.objectContaining({ name: "alpha.jpg", status: "uploading", progress: 30 }),
        expect.objectContaining({ name: "beta.jpg", status: "uploading", progress: 30 }),
      ]);
    });
  });

  it("dispatches the response effects and clears the batch once it lands", async () => {
    const reloaded = vi.fn();
    window.addEventListener("lattice:reload-component", reloaded);
    const { result } = renderUpload();

    act(() => result.current.addFiles([file("alpha.jpg")]));

    await waitFor(() => expect(FakeRequest.instances).toHaveLength(1));
    await act(async () => {
      FakeRequest.instances[0].finish();
    });

    await waitFor(() => expect(result.current.uploads).toEqual([]));

    expect(reloaded).toHaveBeenCalledWith(
      expect.objectContaining({ detail: { component: "media.library" } }),
    );

    window.removeEventListener("lattice:reload-component", reloaded);
  });

  it("marks the batch as failed when the upload is rejected", async () => {
    const { result } = renderUpload();

    act(() => result.current.addFiles([file("alpha.jpg")]));

    await waitFor(() => expect(FakeRequest.instances).toHaveLength(1));
    await act(async () => {
      FakeRequest.instances[0].finish(422, JSON.stringify({ message: "The files are invalid." }));
    });

    await waitFor(() => {
      expect(result.current.uploads).toEqual([
        expect.objectContaining({ name: "alpha.jpg", status: "error" }),
      ]);
    });
  });

  it("marks the batch as failed when the transport reports no status", async () => {
    const { result } = renderUpload();

    act(() => result.current.addFiles([file("alpha.jpg")]));

    await waitFor(() => expect(FakeRequest.instances).toHaveLength(1));
    await act(async () => {
      FakeRequest.instances[0].finish(0, "");
    });

    await waitFor(() => {
      expect(result.current.uploads).toEqual([
        expect.objectContaining({ name: "alpha.jpg", status: "error" }),
      ]);
    });
  });

  it("marks the batch as failed when the request never reaches the server", async () => {
    const { result } = renderUpload();

    act(() => result.current.addFiles([file("alpha.jpg")]));

    await waitFor(() => expect(FakeRequest.instances).toHaveLength(1));
    await act(async () => {
      FakeRequest.instances[0].onerror?.();
    });

    await waitFor(() => {
      expect(result.current.uploads).toEqual([
        expect.objectContaining({ name: "alpha.jpg", status: "error" }),
      ]);
    });
  });

  it("signs, puts the file, then finalizes the keys", async () => {
    apiFetch
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({
            key: "tmp/alpha.jpg",
            url: "https://storage.test/tmp/alpha.jpg?signature=1",
            headers: { "x-amz-acl": "private" },
            method: "PUT",
          }),
          { status: 200 },
        ),
      )
      .mockResolvedValueOnce(new Response(reloadEffects, { status: 200 }));

    const { result } = renderUpload(true);

    act(() => result.current.addFiles([file("alpha.jpg")]));

    await waitFor(() => expect(FakeRequest.instances).toHaveLength(1));

    const request = FakeRequest.instances[0];

    expect(request.method).toBe("PUT");
    expect(request.url).toBe("https://storage.test/tmp/alpha.jpg?signature=1");
    expect(request.headers).toEqual({ "x-amz-acl": "private" });

    await act(async () => {
      request.finish(204, "");
    });

    await waitFor(() => expect(result.current.uploads).toEqual([]));

    expect(apiFetch).toHaveBeenNthCalledWith(
      1,
      "/lattice/actions/media-upload",
      expect.objectContaining({
        body: JSON.stringify({
          _sub: "upload",
          _target: "files",
          filename: "alpha.jpg",
          contentType: "image/jpeg",
        }),
      }),
    );
    expect(apiFetch).toHaveBeenNthCalledWith(
      2,
      "/lattice/actions/media-upload",
      expect.objectContaining({ body: JSON.stringify({ files: ["tmp/alpha.jpg"] }) }),
    );
  });

  it("marks a signed upload as failed when signing is rejected", async () => {
    apiFetch.mockResolvedValue(new Response(null, { status: 422 }));

    const { result } = renderUpload(true);

    act(() => result.current.addFiles([file("alpha.jpg")]));

    await waitFor(() => {
      expect(result.current.uploads).toEqual([
        expect.objectContaining({ name: "alpha.jpg", status: "error" }),
      ]);
    });

    expect(FakeRequest.instances).toEqual([]);
  });

  it("ignores an empty selection and an unavailable endpoint", () => {
    const { result } = renderUpload();

    act(() => result.current.addFiles(null));
    act(() => result.current.addFiles([]));

    const withoutEndpoint = renderHook(() =>
      useMediaUpload({ endpoint: "", ref: "ref-1", signed: false }),
    );

    act(() => withoutEndpoint.result.current.addFiles([file("alpha.jpg")]));

    expect(result.current.uploads).toEqual([]);
    expect(withoutEndpoint.result.current.uploads).toEqual([]);
    expect(FakeRequest.instances).toEqual([]);
  });
});
