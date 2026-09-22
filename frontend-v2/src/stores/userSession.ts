import { acceptHMRUpdate, defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useStorage } from '@vueuse/core'

export type UserData = Model.Auth.LoginData

const useAuthUser = defineStore('authUser', {
  state: () => ({ user: null }),
  getters: {
    user: (state) => state.user,
  },
})

export const useUserSession = defineStore('userSession', () => {
  // token will be synced with local storage
  // @see https://vueuse.org/core/usestorage/
  const token = useStorage('token', '')
  const ruanganfk = useStorage('ruangan_depo', '')
  const kelompokNoCM = useStorage('kelompok_nocm', '')

  const user = ref<Partial<UserData>>()
  const dataUser = useStorage('user_session', '')

  const loading = ref(true)

  const isLoggedIn = computed(() => token.value !== undefined && token.value !== '')

  function setUser(newUser: Partial<UserData>) {
    user.value = newUser
  }

  function setToken(newToken: string) {
    token.value = newToken
  }

  function setRuangan(newRuangan: string) {
    ruanganfk.value = newRuangan
  }

  function setKelompokNoCM(newkelompokNoCM: string) {
    kelompokNoCM.value = newkelompokNoCM
    // kelompokNoCM.value = JSON.stringify(newkelompokNoCM)
  }

  function setUserData(newUser: any) {
    dataUser.value = JSON.stringify(newUser)
  }

  function setLoading(newLoading: boolean) {
    loading.value = newLoading
  }

  function getUser() {
    return JSON.parse(dataUser.value)
  }
  function getProfile() {
    return JSON.parse(dataUser.value).profile
  }

  async function logoutUser() {
    token.value = undefined
    kelompokNoCM.value = undefined
    user.value = undefined
    dataUser.value = ''
  }

  return {
    user,
    token,
    isLoggedIn,
    dataUser,
    loading,
    kelompokNoCM,
    ruanganfk,
    logoutUser,
    setUser,
    getUser,
    setToken,
    setRuangan,
    setKelompokNoCM,
    setLoading,
    setUserData,
    getProfile,
  } as const
})

/**
 * Pinia supports Hot Module replacement so you can edit your stores and
 * interact with them directly in your app without reloading the page.
 *
 * @see https://pinia.esm.dev/cookbook/hot-module-replacement.html
 * @see https://vitejs.dev/guide/api-hmr.html
 */
if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useUserSession, import.meta.hot))
}
