import { useState } from "react";
import { runAction } from "@lattice-php/lattice/action/lib/run-action";
import { apiFetch, xsrfToken } from "@lattice-php/lattice/core/api";
import { withHeaders } from "@lattice-php/lattice/core/headers";
import { useEffectDispatcher } from "@lattice-php/lattice/effects/use-effect-dispatcher";
import type { SignedUpload } from "@lattice-php/lattice/types/generated";

export type UploadItem = {
  id: string;
  name: string;
  status: "uploading" | "error";
  progress: number;
};

export type UploadTarget = {
  endpoint: string;
  ref: string;
  signed: boolean;
};

export type MediaUpload = {
  uploads: UploadItem[];
  addFiles: (files: FileList | File[] | null) => void;
};

type Transfer = {
  url: string;
  method: string;
  body: FormData | File;
  headers: Record<string, unknown>;
  onProgress: (percent: number) => void;
};

/**
 * XHR rather than fetch because only XHR reports upload progress. Resolving a
 * Response keeps runAction — and with it the shared effect dispatch and error
 * policy — usable for both upload flows.
 */
function transfer({ url, method, body, headers, onProgress }: Transfer): Promise<Response> {
  return new Promise((resolve, reject) => {
    const request = new XMLHttpRequest();

    request.open(method, url, true);
    Object.entries(headers).forEach(([key, value]) => request.setRequestHeader(key, String(value)));
    request.upload.onprogress = (event) => {
      if (event.lengthComputable) {
        onProgress(Math.round((event.loaded / event.total) * 100));
      }
    };
    request.onload = () => {
      try {
        resolve(new Response(request.responseText || null, { status: request.status || 500 }));
      } catch {
        reject(new Error(`Upload to ${url} returned an unusable response`));
      }
    };
    request.onerror = () => reject(new Error(`Upload to ${url} failed`));
    request.send(body);
  });
}

/**
 * Drives the media library's uploads. A settled item leaves the list — the
 * `reload-component` effect the upload action emits brings the new row into the
 * grid — so `uploads` only ever holds in-flight and failed files.
 */
export function useMediaUpload({ endpoint, ref, signed }: UploadTarget): MediaUpload {
  const dispatch = useEffectDispatcher();
  const [uploads, setUploads] = useState<UploadItem[]>([]);

  function update(ids: string[], changes: Partial<UploadItem>): void {
    setUploads((previous) =>
      previous.map((item) => (ids.includes(item.id) ? { ...item, ...changes } : item)),
    );
  }

  function settle(ids: string[], ok: boolean): void {
    if (!ok) {
      update(ids, { status: "error" });

      return;
    }

    setUploads((previous) => previous.filter((item) => !ids.includes(item.id)));
  }

  async function uploadMultipart(items: UploadItem[], files: File[]): Promise<void> {
    const body = new FormData();
    files.forEach((file) => body.append("files[]", file));
    const ids = items.map((item) => item.id);

    const ok = await runAction(
      () =>
        transfer({
          url: endpoint,
          method: "POST",
          body,
          headers: withHeaders(ref, {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-XSRF-TOKEN": xsrfToken(),
          }),
          onProgress: (progress) => update(ids, { progress }),
        }),
      dispatch,
    );

    settle(ids, ok);
  }

  async function signAndPut(item: UploadItem, file: File): Promise<string | null> {
    const signature = await apiFetch(endpoint, {
      method: "POST",
      ref,
      body: JSON.stringify({
        _sub: "upload",
        _target: "files",
        filename: file.name,
        contentType: file.type,
      }),
      throwOnError: false,
    });

    if (!signature.ok) {
      update([item.id], { status: "error" });

      return null;
    }

    const sign = (await signature.json()) as SignedUpload;

    try {
      const put = await transfer({
        url: sign.url,
        method: sign.method.toUpperCase(),
        body: file,
        headers: sign.headers,
        onProgress: (progress) => update([item.id], { progress }),
      });

      if (!put.ok) {
        update([item.id], { status: "error" });

        return null;
      }

      return sign.key;
    } catch {
      update([item.id], { status: "error" });

      return null;
    }
  }

  async function uploadSigned(items: UploadItem[], files: File[]): Promise<void> {
    const keys = await Promise.all(items.map((item, index) => signAndPut(item, files[index])));
    const uploaded = keys.filter((key): key is string => key !== null);

    if (uploaded.length === 0) {
      return;
    }

    const ok = await runAction(
      () =>
        apiFetch(endpoint, {
          method: "POST",
          ref,
          body: JSON.stringify({ files: uploaded }),
          throwOnError: false,
        }),
      dispatch,
    );

    settle(
      items.filter((_, index) => keys[index] !== null).map((item) => item.id),
      ok,
    );
  }

  function addFiles(incoming: FileList | File[] | null): void {
    const files = Array.from(incoming ?? []);

    if (files.length === 0 || endpoint === "") {
      return;
    }

    const items = files.map<UploadItem>((file) => ({
      id: crypto.randomUUID(),
      name: file.name,
      status: "uploading",
      progress: 0,
    }));

    setUploads((previous) => [...previous, ...items]);
    void (signed ? uploadSigned(items, files) : uploadMultipart(items, files));
  }

  return { uploads, addFiles };
}
