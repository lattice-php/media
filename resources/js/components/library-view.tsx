import { useState } from "react";
import { runAction } from "@lattice-php/lattice/action/lib/run-action";
import { apiFetch } from "@lattice-php/lattice/core/api";
import type { Node } from "@lattice-php/lattice/core/types";
import { useEffectDispatcher } from "@lattice-php/lattice/effects/use-effect-dispatcher";
import { useT } from "@lattice-php/lattice/i18n";
import { useDebouncedCallback } from "@lattice-php/lattice/lib/use-debounced-callback";
import { cn } from "@lattice-php/lattice/lib/utils";
import { useTable } from "@lattice-php/lattice/table/hooks/use-table";
import { useTableSelection } from "@lattice-php/lattice/table/hooks/use-table-selection";
import { getBulkActions } from "@lattice-php/lattice/table/lib/bulk";
import type { BulkAction } from "@lattice-php/lattice/table/lib/bulk";
import type { TableNode } from "@lattice-php/lattice/table/types";
import { Button } from "@lattice-php/lattice/ui/button";
import { Checkbox } from "@lattice-php/lattice/ui/checkbox";
import { Input } from "@lattice-php/lattice/ui/input";
import { NativeSelect } from "@lattice-php/lattice/ui/native-select";

const SEARCH_DEBOUNCE_MS = 300;

export type MediaRow = {
  id: number;
  url: string | null;
  name: string;
  mime_type: string;
  size: number;
  alt: string | null;
  created_at: string;
  attachments_count: number;
};

export type PickMode = { multiple: boolean; onConfirm: (items: MediaRow[]) => void };

/**
 * The grid face of the media table: it drives the same `useTable` state the
 * core table component does, so search, filters, infinite paging and the
 * `reload-component` effect all behave identically — only the presentation and
 * the selection affordances differ.
 */
export function LibraryView({ node, pick }: { node: Node; pick?: PickMode }) {
  const { t } = useT("media");
  const tableNode = (node.schema?.find((child) => child.type === "table") ?? {
    type: "table",
  }) as TableNode;
  const table = useTable(tableNode);
  const rows = table.rows as MediaRow[];
  const selection = useTableSelection(rows.map((row) => String(row.id)));
  const [deleteAction] = getBulkActions(tableNode.props?.bulkActions);
  const commitSearch = useDebouncedCallback(
    (term: string) => table.setSearch(term),
    SEARCH_DEBOUNCE_MS,
  );

  function toggle(row: MediaRow): void {
    if (pick && !pick.multiple) {
      selection.clear();
    }

    selection.toggle(String(row.id));
  }

  return (
    <div className="flex flex-col gap-4" data-test="media-library">
      <div className="flex flex-wrap items-center gap-3">
        <Input
          className="max-w-xs"
          data-test="media-search"
          defaultValue={table.search}
          onChange={(event) => commitSearch(event.target.value)}
          placeholder={t("media.library.search", "Search media")}
          type="search"
        />
        <NativeSelect
          aria-label={t("media.filters.type.label", "Type")}
          className="max-w-40"
          data-test="media-type-filter"
          defaultValue=""
          onChange={(event) => table.setTableFilter("type", { value: event.target.value })}
        >
          <option value="">{t("media.filters.type.all", "All types")}</option>
          <option value="image">{t("media.filters.type.image", "Images")}</option>
          <option value="video">{t("media.filters.type.video", "Video")}</option>
          <option value="audio">{t("media.filters.type.audio", "Audio")}</option>
          <option value="document">{t("media.filters.type.document", "Documents")}</option>
        </NativeSelect>
      </div>

      {rows.length === 0 && table.hasLoaded ? (
        <p className="py-12 text-center text-sm text-lt-muted-fg" data-test="media-empty">
          {t("media.library.empty", "No media yet. Drop files anywhere to upload.")}
        </p>
      ) : (
        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
          {rows.map((row) => (
            <li className="relative" key={row.id}>
              <button
                className={cn(
                  "flex w-full flex-col overflow-hidden rounded-lt-sm border border-lt-border bg-lt-surface text-left",
                  selection.isSelected(String(row.id)) &&
                    "ring-[length:var(--lt-ring-width)] ring-lt-ring",
                )}
                data-test="media-card"
                onClick={() => toggle(row)}
                type="button"
              >
                {row.url !== null && row.mime_type.startsWith("image/") ? (
                  <img
                    alt={row.alt ?? row.name}
                    className="aspect-square w-full object-cover"
                    src={row.url}
                  />
                ) : (
                  <span className="flex aspect-square w-full items-center justify-center text-sm text-lt-muted-fg">
                    {row.mime_type.split("/")[1] ?? row.mime_type}
                  </span>
                )}
                <span className="truncate px-2 py-1.5 text-sm text-lt-fg">{row.name}</span>
              </button>
              <Checkbox
                aria-label={row.name}
                checked={selection.isSelected(String(row.id))}
                className="absolute left-2 top-2 bg-lt-surface"
                data-test="media-card-select"
                onCheckedChange={() => toggle(row)}
              />
            </li>
          ))}
        </ul>
      )}

      <div ref={table.infiniteLoaderRef} />

      {pick ? (
        <div className="flex justify-end border-t border-lt-border pt-3">
          <Button
            data-test="media-pick-confirm"
            disabled={!selection.active}
            onClick={() =>
              pick.onConfirm(rows.filter((row) => selection.isSelected(String(row.id))))
            }
            type="button"
            variant="primary"
          >
            {t("media.picker.confirm", "Select {{count}} item(s)", {
              count: selection.selectedKeys.length,
            })}
          </Button>
        </div>
      ) : (
        deleteAction &&
        selection.active && (
          <BulkDeleteBar
            action={deleteAction}
            onDone={selection.clear}
            selectedKeys={selection.selectedKeys}
          />
        )
      )}
    </div>
  );
}

function BulkDeleteBar({
  action,
  selectedKeys,
  onDone,
}: {
  action: BulkAction;
  selectedKeys: string[];
  onDone: () => void;
}) {
  const { t } = useT("media");
  const dispatch = useEffectDispatcher();
  const [processing, setProcessing] = useState(false);

  async function submit(): Promise<void> {
    setProcessing(true);

    const ok = await runAction(
      () =>
        apiFetch(action.endpoint, {
          method: action.method,
          ref: action.ref,
          body: JSON.stringify({ selected: selectedKeys }),
          throwOnError: false,
        }),
      dispatch,
    );

    setProcessing(false);

    if (ok) {
      onDone();
    }
  }

  return (
    <div className="sticky bottom-0 z-lt-sticky flex items-center justify-between gap-3 rounded-lt-sm border border-lt-border bg-lt-surface px-4 py-3 text-sm shadow-lt-md">
      <span>
        {t("media.library.selected", "{{count}} selected", { count: selectedKeys.length })}
      </span>
      <Button
        data-test="media-bulk-delete"
        disabled={processing}
        emphasis={action.emphasis ?? "solid"}
        onClick={() => void submit()}
        type="button"
        variant={action.variant ?? "danger"}
      >
        {action.label}
      </Button>
    </div>
  );
}
