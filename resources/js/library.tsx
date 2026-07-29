import type { RendererComponent } from "@lattice-php/lattice/core/types";
import { LibraryView } from "./components/library-view";

const MediaLibraryComponent: RendererComponent<"media.library"> = ({ node }) => (
  <LibraryView node={node} />
);

export default MediaLibraryComponent;
