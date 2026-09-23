export function createIrisScene(
  host: HTMLElement,
  options: { paused?: boolean; onReady: () => void; onFailure: () => void }
): { setPaused: (paused: boolean) => void; dispose: () => void }
