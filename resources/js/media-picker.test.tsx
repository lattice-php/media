import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { FormProvider } from "@lattice-php/lattice/form/hooks/context";
import { FormValuesProvider } from "@lattice-php/lattice/form/hooks/values";
import { fakeFormContext, fakeNode } from "@lattice-php/lattice/test-support";
import MediaPickerComponent from "./media-picker";

function renderPicker(props: Record<string, unknown> = {}) {
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
});
