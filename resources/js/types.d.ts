import "@lattice-php/lattice";
import type { ColumnWidth, FieldConditions } from "@lattice-php/lattice/types/generated";

declare module "@lattice-php/lattice" {
  interface ComponentProps {
    "media.library": {
      picker: boolean;
      multiple: boolean;
      accept: string | null;
      maxSize: number | null;
      signed: boolean;
    };
    "field.media-picker": {
      columnWidth: ColumnWidth;
      conditions: FieldConditions | null;
      dependsOnAny: boolean;
      dependsOnKeys: string[] | null;
      disabled: boolean;
      editablePrefill: boolean;
      helperText: string | null;
      label: string | null;
      maxFiles: number | null;
      multiple: boolean;
      name: string;
      prefillRefreshOn: string[] | null;
      prefillResetOn: string[] | null;
      readOnly: boolean;
      required: boolean;
      selected: { id: number; name: string; url: string | null; mime_type: string }[] | null;
      tooltip: string | null;
      value: unknown;
    };
  }
}
