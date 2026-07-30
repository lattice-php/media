import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import type { Schema } from "@lattice-php/lattice/core/types";
import { fakeNode } from "../test-support";
import { LibraryView } from "./library-view";

function row(id: number) {
  return {
    id,
    url: null,
    name: `file-${id}.jpg`,
    mime_type: "image/jpeg",
    size: 100,
    alt: null,
    created_at: "2026-07-29T00:00:00Z",
    attachments_count: 0,
  };
}

function libraryNode() {
  return fakeNode({
    type: "media.library",
    props: { picker: true, accept: null, signed: false },
    schema: [{ type: "table", props: { columns: [], data: [row(1), row(2)] } }] as Schema,
  });
}

describe("LibraryView", () => {
  it("deselects a single-select pick when the same card is clicked twice", () => {
    render(<LibraryView node={libraryNode()} pick={{ multiple: false, onConfirm: vi.fn() }} />);

    const [card] = screen.getAllByTestId("media-card");

    fireEvent.click(card);
    expect(screen.getByTestId("media-pick-confirm")).toBeEnabled();

    fireEvent.click(card);
    expect(screen.getByTestId("media-pick-confirm")).toBeDisabled();
  });

  it("shows a selection counter once a max is configured", () => {
    render(
      <LibraryView node={libraryNode()} pick={{ multiple: true, max: 2, onConfirm: vi.fn() }} />,
    );

    expect(screen.getByTestId("media-pick-counter")).toHaveTextContent("0/2");

    fireEvent.click(screen.getAllByTestId("media-card")[0]);

    expect(screen.getByTestId("media-pick-counter")).toHaveTextContent("1/2");
  });

  it("refuses to select past the max but always allows deselecting", () => {
    render(
      <LibraryView node={libraryNode()} pick={{ multiple: true, max: 1, onConfirm: vi.fn() }} />,
    );

    const [first, second] = screen.getAllByTestId("media-card");

    fireEvent.click(first);
    expect(screen.getByTestId("media-pick-confirm")).toBeEnabled();

    fireEvent.click(second);
    expect(screen.getByTestId("media-pick-counter")).toHaveTextContent("1/1");

    fireEvent.click(first);
    expect(screen.getByTestId("media-pick-confirm")).toBeDisabled();

    fireEvent.click(second);
    expect(screen.getByTestId("media-pick-counter")).toHaveTextContent("1/1");
  });
});
