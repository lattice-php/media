import { useState } from "react";
import type { RendererComponent } from "@lattice-php/lattice/core/types";
import { SimpleField } from "@lattice-php/lattice/form/components/fields/simple-field";
import { translate, useT } from "@lattice-php/lattice/i18n";
import { Button } from "@lattice-php/lattice/ui/button";
import { Dialog, DialogContent, DialogHeader } from "@lattice-php/lattice/ui/dialog";
import { IconButton } from "@lattice-php/lattice/ui/icon-button";
import { LibraryView, type MediaRow } from "./components/library-view";

type Picked = {
  id: number;
  name: string;
  url: string | null;
  preview_url: string | null;
  mime_type: string;
};

const MediaPickerComponent: RendererComponent<"field.media-picker"> = ({ node }) => {
  const { t } = useT("media");
  const props = node.props;
  const [open, setOpen] = useState(false);
  const [picked, setPicked] = useState<Picked[]>(props.selected ?? []);
  const libraryNode = node.schema?.find((child) => child.type === "media.library");
  const multiple = props.multiple;
  const maxFiles = props.maxFiles;
  const remaining =
    multiple && maxFiles !== null ? Math.max(0, maxFiles - picked.length) : undefined;

  return (
    <SimpleField label={props.label ?? ""} node={node}>
      {({ name, commit, disabled, readOnly }) => {
        const locked = disabled || readOnly;
        const apply = (next: Picked[]): void => {
          setPicked(next);
          commit(multiple ? next.map((entry) => entry.id) : (next[0]?.id ?? ""));
        };

        return (
          <div className="flex flex-col gap-2" data-test={`media-picker-${name}`}>
            {multiple ? (
              picked.map((item) => (
                <input key={item.id} name={`${name}[]`} type="hidden" value={item.id} />
              ))
            ) : (
              <input name={name} type="hidden" value={picked[0]?.id ?? ""} />
            )}

            {picked.length > 0 && (
              <ul className="flex flex-wrap gap-2">
                {picked.map((item) => (
                  <li
                    className="flex max-w-56 items-center gap-2 rounded-lt-sm border border-lt-border bg-lt-surface px-2 py-1 text-sm"
                    data-test="media-picker-item"
                    key={item.id}
                  >
                    {item.preview_url !== null && item.mime_type.startsWith("image/") && (
                      <img
                        alt=""
                        className="size-8 rounded-lt-xs object-cover"
                        src={item.preview_url}
                      />
                    )}
                    <span className="truncate text-lt-fg">{item.name}</span>
                    {!locked && (
                      <IconButton
                        data-test="media-picker-remove"
                        icon="x"
                        label={t("media.picker.remove", "Remove {{name}}", { name: item.name })}
                        onClick={() => apply(picked.filter((entry) => entry.id !== item.id))}
                      />
                    )}
                  </li>
                ))}
              </ul>
            )}

            <Button
              className="self-start"
              data-test="media-picker-open"
              disabled={locked}
              onClick={() => setOpen(true)}
              type="button"
            >
              {t("media.picker.open", "Choose from library")}
            </Button>

            {open && libraryNode && (
              <Dialog onOpenChange={setOpen} open>
                <DialogContent
                  aria-describedby={undefined}
                  className="flex flex-col gap-5"
                  data-test="media-picker-dialog"
                  width="3xl"
                >
                  <DialogHeader
                    closeLabel={translate("lattice", "common.close", "Close")}
                    title={t("media.picker.heading", "Choose media")}
                  />
                  <LibraryView
                    node={libraryNode}
                    pick={{
                      multiple,
                      max: remaining,
                      onConfirm: (items: MediaRow[]) => {
                        const merged = multiple
                          ? [
                              ...picked.filter(
                                (entry) => !items.some((item) => item.id === entry.id),
                              ),
                              ...items,
                            ]
                          : items.slice(0, 1);

                        apply(
                          multiple && maxFiles !== null ? merged.slice(0, maxFiles) : merged,
                        );
                        setOpen(false);
                      },
                    }}
                  />
                </DialogContent>
              </Dialog>
            )}
          </div>
        );
      }}
    </SimpleField>
  );
};

export default MediaPickerComponent;
