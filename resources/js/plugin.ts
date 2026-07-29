import { createPlugin, lazyComponent } from "@lattice-php/lattice";

export default createPlugin({
  name: "media",
  components: {
    "media.library": lazyComponent(() => import("./library")),
  },
  i18n: { namespace: "media" },
});
