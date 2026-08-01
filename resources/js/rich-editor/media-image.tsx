import { mergeAttributes, Node } from "@tiptap/core";
import { NodeViewWrapper, ReactNodeViewRenderer, type NodeViewProps } from "@tiptap/react";
import { useT } from "@lattice-php/lattice/i18n";
import { cn } from "@lattice-php/lattice/lib/utils";
import { Input } from "@lattice-php/lattice/ui/input";
import { NativeSelect } from "@lattice-php/lattice/ui/native-select";

type MediaImageOptions = { conversions: string[] };

export function MediaImageView({ editor, extension, node, selected, updateAttributes }: NodeViewProps) {
  const { t } = useT("media");
  const conversions = (extension.options as MediaImageOptions).conversions;
  const url = node.attrs.url as string | null;
  const alt = (node.attrs.alt ?? node.attrs.mediaAlt ?? "") as string;

  return (
    <NodeViewWrapper className="flex flex-col gap-2" data-test="editor-media-image">
      {url ? (
        <img
          alt={alt}
          className={cn("max-w-full rounded-lt-sm", selected && "ring-2 ring-lt-ring")}
          src={url}
        />
      ) : (
        <div
          className="rounded-lt-sm border border-dashed border-lt-border px-3 py-2 text-sm text-lt-fg-muted"
          data-test="editor-media-image-missing"
        >
          {t("media.editor.missing", "Missing media")}
        </div>
      )}
      {selected && editor.isEditable && (
        <div className="flex items-center gap-2" data-test="editor-media-image-controls">
          <Input
            aria-label={t("media.editor.alt", "Alt text")}
            onChange={(event) =>
              updateAttributes({ alt: event.target.value === "" ? null : event.target.value })
            }
            placeholder={t("media.editor.alt", "Alt text")}
            value={(node.attrs.alt ?? "") as string}
          />
          {conversions.length > 0 && (
            <NativeSelect
              aria-label={t("media.editor.size", "Size")}
              onChange={(event) =>
                updateAttributes({ conversion: event.target.value === "" ? null : event.target.value })
              }
              value={(node.attrs.conversion ?? "") as string}
            >
              <option value="">{t("media.editor.original", "Original")}</option>
              {conversions.map((name) => (
                <option key={name} value={name}>
                  {name}
                </option>
              ))}
            </NativeSelect>
          )}
        </div>
      )}
    </NodeViewWrapper>
  );
}

export const MediaImageNode = Node.create<MediaImageOptions>({
  name: "mediaImage",
  group: "block",
  atom: true,
  draggable: true,

  addOptions() {
    return { conversions: [] };
  },

  addAttributes() {
    return {
      id: { default: null },
      alt: { default: null },
      conversion: { default: null },
      url: { default: null },
      width: { default: null },
      height: { default: null },
      mediaAlt: { default: null },
    };
  },

  parseHTML() {
    return [{ tag: "img[data-media-id]" }];
  },

  renderHTML({ node, HTMLAttributes }) {
    return [
      "img",
      mergeAttributes(HTMLAttributes, {
        src: node.attrs.url,
        alt: (node.attrs.alt ?? node.attrs.mediaAlt ?? "") as string,
        "data-media-id": node.attrs.id,
      }),
    ];
  },

  addNodeView() {
    return ReactNodeViewRenderer(MediaImageView);
  },
});
