// View Transitions API — TypeScript typings.
// `lib.dom.d.ts` ships these now in TS 5.6+, but we keep an explicit
// declaration for portability and clearer intent.

interface ViewTransition {
  readonly finished: Promise<void>;
  readonly ready: Promise<void>;
  readonly updateCallbackDone: Promise<void>;
  skipTransition(): void;
}

interface Document {
  startViewTransition?: (updateCallback: () => void | Promise<void>) => ViewTransition;
}
