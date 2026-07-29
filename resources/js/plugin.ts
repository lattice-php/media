import { createPlugin, lazyComponent } from "@lattice-php/lattice";

export default createPlugin({
  name: "media",
  components: {
    "media.library": lazyComponent(() => import("./library")),
    "field.media-picker": lazyComponent(() => import("./media-picker")),
  },
  i18n: { namespace: "media" },
});
