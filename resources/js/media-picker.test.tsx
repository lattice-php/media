import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import type { Schema } from "@lattice-php/lattice/core/types";
import { FormProvider } from "@lattice-php/lattice/form/hooks/context";
import { FormValuesProvider } from "@lattice-php/lattice/form/hooks/values";
import { fakeFormContext, fakeNode } from "./test-support";
import MediaPickerComponent from "./media-picker";

function libraryRow(id: number, name: string) {
  return {
    id,
    url: null,
    name,
    mime_type: "image/jpeg",
    size: 100,
    alt: null,
    created_at: "2026-07-29T00:00:00Z",
    attachments_count: 0,
  };
}

function librarySchema(rows: ReturnType<typeof libraryRow>[]): Schema {
  return [
    {
      type: "media.library",
      props: { picker: true, accept: null, signed: false },
      schema: [{ type: "table", props: { columns: [], data: rows } }],
    },
  ] as Schema;
}

function renderPicker(props: Record<string, unknown> = {}, schema?: Schema) {
  const node = fakeNode({
    type: "field.media-picker",
    props: {
      name: "cover",
      label: "Cover",
      multiple: false,
      maxFiles: null,
      selected: [{ id: 7, name: "cover.jpg", url: null, mime_type: "image/jpeg" }],
      ...props,
    },
    schema,
  });

  return render(
    <FormProvider value={fakeFormContext({ action: "/forms/products", componentRef: "ref-1" })}>
      <FormValuesProvider initial={{}}>
        <MediaPickerComponent node={node}>{null}</MediaPickerComponent>
      </FormValuesProvider>
    </FormProvider>,
  );
}

describe("MediaPickerComponent", () => {
  it("submits the single selected id through one hidden input", () => {
    const { container } = renderPicker();

    expect(screen.getByTestId("media-picker-cover")).toHaveTextContent("cover.jpg");
    expect(container.querySelector('input[type="hidden"][name="cover"]')).toHaveValue("7");
  });

  it("clears the submitted value when the chip is removed", () => {
    const { container } = renderPicker();

    fireEvent.click(screen.getByTestId("media-picker-remove"));

    expect(container.querySelector('input[type="hidden"][name="cover"]')).toHaveValue("");
    expect(screen.queryByTestId("media-picker-item")).toBeNull();
  });

  it("submits one hidden input per id in multiple mode", () => {
    const { container } = renderPicker({
      multiple: true,
      selected: [
        { id: 7, name: "a.jpg", url: null, mime_type: "image/jpeg" },
        { id: 9, name: "b.jpg", url: null, mime_type: "image/jpeg" },
      ],
    });

    expect(container.querySelectorAll('input[type="hidden"][name="cover[]"]')).toHaveLength(2);
  });

  it("hides the remove and open affordances when the field is read only", () => {
    renderPicker({ readOnly: true });

    expect(screen.queryByTestId("media-picker-remove")).toBeNull();
    expect(screen.getByTestId("media-picker-open")).toBeDisabled();
  });

  it("merging newly picked items with already-picked ones respects maxFiles", () => {
    const { container } = renderPicker(
      {
        multiple: true,
        maxFiles: 2,
        selected: [{ id: 1, name: "a.jpg", url: null, mime_type: "image/jpeg" }],
      },
      librarySchema([libraryRow(10, "d.jpg"), libraryRow(11, "e.jpg")]),
    );

    fireEvent.click(screen.getByTestId("media-picker-open"));
    fireEvent.click(screen.getAllByTestId("media-card")[0]);
    fireEvent.click(screen.getAllByTestId("media-card")[1]);
    fireEvent.click(screen.getByTestId("media-pick-confirm"));

    expect(container.querySelectorAll('input[type="hidden"][name="cover[]"]')).toHaveLength(2);
  });
});
