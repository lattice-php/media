import "@lattice-php/lattice";

declare module "@lattice-php/lattice" {
  interface ComponentProps {
    "media.library": {
      picker: boolean;
      multiple: boolean;
      accept: string | null;
      maxSize: number | null;
      signed: boolean;
    };
  }
}
