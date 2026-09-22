/**
 * We can extends the env types here
 * @see https://vitejs.dev/guide/env-and-mode.html#env-files
 */

interface ImportMetaEnv extends Readonly<Record<string, string>> {
  readonly VITE_API_BASE_URL: string
  readonly VITE_MAPBOX_ACCESS_TOKEN: string
  readonly VITE_NAVIGASI: string
  readonly VITE_PROJECT: string
  readonly VITE_NODE_ENV: string
  readonly VITE_SOCKET_ON: boolean
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
