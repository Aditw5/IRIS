<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

type ChatRole = 'user' | 'bot'

interface ChatMessage {
  id: number
  role: ChatRole
  text: string
  mode?: string
  intent?: string
}

interface ParsedMessage {
  type: 'text' | 'numbered'
  text: string
  intro: string
  items: string[]
}

interface ChatbotResponse {
  success?: boolean
  mode?: string
  intent?: string
  question?: string
  kelompokUser?: string
  search?: string
  answer?: string
  backend_status_code?: number
  backend_url?: string
  matched_files?: string[]
  sources?: any[]
  backend_response?: any
}

interface ChatbotKelompokConfig {
  show: boolean
  subtitle: string
  greetingFallback: string
  welcomeText: (name: string) => string
  faqs: string[]
}


const CHATBOT_KELOMPOK_CONFIG: Record<string, ChatbotKelompokConfig> = {
  customer: {
    show: true,
    subtitle: 'Asisten layanan customer',
    greetingFallback: 'Customer',
    welcomeText: (name: string) =>
      `Halo ${name}, saya asisten U-LAB. Silakan tanyakan cara penggunaan web atau status layanan customer.`,
    faqs: [
      'Bagaimana cara mengisi survey pelanggan?',
      'Bagaimana cara mencetak sertifikat?',
      'Bagaimana cara menambahkan alat?',
      'Keranjang saya ada apa saja?',
      'Alat yang belum selesai ada berapa?',
      'Bagaimana cara memasukkan alat ke keranjang?',
      'Bagaimana melihat progress alat?',
      'Bagaimana cara checkout order?',
      'Bagaimana cara Merubah nomor WhatsApp?',
    ],
  },

  registrasi: {
    show: true,
    subtitle: 'Asisten layanan registrasi',
    greetingFallback: 'Registrasi',
    welcomeText: (name: string) =>
      `Halo ${name}, saya asisten U-LAB. Silakan tanyakan panduan proses Registrasi, mulai dari unit baru, registrasi alat, verifikasi, kaji ulang, master data, akun pegawai, sampai cetak dokumen.`,
    faqs: [
      // 'Bagaimana cara login Registrasi?',
      'Bagaimana cara mendaftarkan Unit Baru?',
      'Bagaimana cara registrasi alat melalui admin?',
      'Bagaimana cara menambahkan alat baru ke master data?',
      'Bagaimana cara verifikasi registrasi customer?',
      'Bagaimana cara kaji ulang pendaftaran unit?',
      'Bagaimana cara cetak tanda terima alat?',
      'Bagaimana cara cetak label order?',
      'Bagaimana cara cetak label selesai kalibrasi?',
      'Bagaimana cara cetak tanda terima selesai?',
      'Bagaimana cara cetak sertifikat kalibrasi atau laporan repair?',
      'Bagaimana cara membuat rekalibrasi standar internal?',

      'Bagaimana cara menambahkan pegawai baru?',
      'Bagaimana cara membuat akun login pegawai?',
      'Bagaimana jika nama pegawai belum ada saat membuat akun login?',
      'Bagaimana cara memilih kelompok user untuk akun pegawai?',
      'Bagaimana cara mapping modul aplikasi untuk akun pegawai?',

      'Bagaimana cara menambahkan data Ruangan?',
      'Bagaimana cara menambahkan ruangan baru?',
      'Bagaimana cara menambahkan data Agama?',
      'Bagaimana cara menambahkan agama baru?',
      'Bagaimana cara menambahkan data Jenis Kelamin?',
      'Bagaimana cara menambahkan jenis kelamin baru?',
      'Bagaimana cara menambahkan data Jabatan?',
      'Bagaimana cara menambahkan jabatan baru?',
      'Bagaimana cara menambahkan data Instruksi Kerja?',
      'Bagaimana cara menambahkan instruksi kerja baru?',
      'Bagaimana cara menambahkan data Alat Standar?',
      'Bagaimana cara menambahkan alat standar baru?',
      'Bagaimana cara menambahkan data Vendor?',
      'Bagaimana cara menambahkan vendor baru?',

      'Bagaimana cara input catatan Synergy Pagi?',
      'Bagaimana cara input RBK dan realisasi RBK?',
      'Bagaimana cara upload dokumen ke UDS?',

      'Bagaimana cara melihat survey kepuasan pelanggan?',
      'Bagaimana cara melihat laporan penilaian pelanggan?',
      'Bagaimana cara mengirim peringatan isi survey pelanggan?',
      'Apa arti icon message pada survey pelanggan?',
      'Apa arti icon mata pada survey pelanggan?',
      'Bagaimana mengetahui pelanggan sudah mengisi survey kepuasan?',

      'Kenapa PDF tanda terima, label, sertifikat, atau laporan tidak terbuka?',
    ],
  },
}

const DEFAULT_CHATBOT_CONFIG: ChatbotKelompokConfig = {
  show: false,
  subtitle: 'Asisten layanan U-LAB',
  greetingFallback: 'User',
  welcomeText: (name: string) =>
    `Halo ${name}, saya asisten U-LAB. Silakan tanyakan panduan penggunaan sistem.`,
  faqs: [
    'Bagaimana cara menggunakan sistem U-LAB?',
    'Bagaimana cara melihat data pada dashboard?',
  ],
}

const props = withDefaults(
  defineProps<{
    kelompokUser?: string
    userName?: string
    onlyCustomer?: boolean
  }>(),
  {
    kelompokUser: '',
    userName: '',
    onlyCustomer: true,
  }
)

const CHATBOT_URL =
  import.meta.env.VITE_CHATBOT_URL || 'https://ulabumro.id/chat-bot/ask'

const isOpen = ref(false)
const isPinned = ref(false)
const isLoading = ref(false)
const inputMessage = ref('')
const messages = ref<ChatMessage[]>([])
const chatBodyRef = ref<HTMLElement | null>(null)
const isMobileScreen = ref(false)
const isAndroidDevice = ref(false)
const isLauncherIdle = ref(false)
const isFaqOpen = ref(true)
let launcherIdleTimer: ReturnType<typeof setTimeout> | null = null

const LAUNCHER_IDLE_DELAY = 7000

const normalizeKelompokUser = (value: string | undefined | null) => {
  return String(value || '')
    .toLowerCase()
    .trim()
    .replace(/\s+/g, ' ')
}

const normalizedKelompokUser = computed(() => {
  return normalizeKelompokUser(props.kelompokUser)
})

const currentKelompokConfig = computed(() => {
  return CHATBOT_KELOMPOK_CONFIG[normalizedKelompokUser.value] || DEFAULT_CHATBOT_CONFIG
})

const shouldShowWidget = computed(() => {
  if (!props.onlyCustomer) return true

  if (!normalizedKelompokUser.value) return false

  return currentKelompokConfig.value.show === true
})

const greetingName = computed(() => {
  return props.userName ? props.userName : currentKelompokConfig.value.greetingFallback
})

const chatbotSubtitle = computed(() => {
  return currentKelompokConfig.value.subtitle
})

const welcomeText = computed(() => {
  return currentKelompokConfig.value.welcomeText(greetingName.value)
})

const quickFaq = computed(() => {
  return currentKelompokConfig.value.faqs
})

const faqTitleText = computed(() => {
  if (normalizedKelompokUser.value === 'registrasi') {
    return 'Rekomendasi pertanyaan Registrasi'
  }

  if (normalizedKelompokUser.value === 'customer') {
    return 'Rekomendasi pertanyaan Customer'
  }

  return 'Rekomendasi pertanyaan'
})

const hasUserQuestion = computed(() => {
  return messages.value.some((message) => message.role === 'user')
})

const checkScreen = () => {
  if (typeof window !== 'undefined') {
    isMobileScreen.value = window.innerWidth <= 768
    isAndroidDevice.value = /Android/i.test(window.navigator.userAgent)
  }
}

const clearLauncherIdleTimer = () => {
  if (launcherIdleTimer) {
    clearTimeout(launcherIdleTimer)
    launcherIdleTimer = null
  }
}

const scheduleLauncherIdle = () => {
  clearLauncherIdleTimer()
  isLauncherIdle.value = false

  if (!isMobileScreen.value || !isAndroidDevice.value || isOpen.value) return

  launcherIdleTimer = setTimeout(() => {
    if (isMobileScreen.value && isAndroidDevice.value && !isOpen.value) {
      isLauncherIdle.value = true
    }
  }, LAUNCHER_IDLE_DELAY)
}

watch(
  [isOpen, isMobileScreen, isAndroidDevice],
  () => {
    scheduleLauncherIdle()
  }
)

const ensureWelcomeMessage = () => {
  if (shouldShowWidget.value && messages.value.length === 0) {
    messages.value.push({
      id: Date.now(),
      role: 'bot',
      text: welcomeText.value,
    })
  }
}

onMounted(() => {
  checkScreen()
  window.addEventListener('resize', checkScreen)
  ensureWelcomeMessage()
  scheduleLauncherIdle()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkScreen)
  clearLauncherIdleTimer()
})

watch(
  () => shouldShowWidget.value,
  () => {
    ensureWelcomeMessage()
  }
)

const CHATBOT_Z_INDEX = 900

const rootStyle = computed(() => {
  return {
    position: 'fixed',
    right: isMobileScreen.value ? '12px' : '24px',
    left: 'auto',
    bottom: isMobileScreen.value ? '118px' : '26px',
    top: 'auto',
    zIndex: String(CHATBOT_Z_INDEX),
    width: 'auto',
    height: 'auto',
    display: shouldShowWidget.value ? 'block' : 'none',
    pointerEvents: 'auto',
    fontFamily:
      "Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
  } as Record<string, string>
})

const panelStyle = computed(() => {
  if (isMobileScreen.value) {
    return {
      position: 'fixed',
      right: '12px',
      left: '12px',
      bottom: '170px',
      width: 'calc(100vw - 24px)',
      height: 'min(500px, calc(100vh - 210px))',
      maxHeight: 'calc(100vh - 210px)',
      background: '#ffffff',
      borderRadius: '24px',
      boxShadow: '0 24px 70px rgba(15, 23, 42, 0.26)',
      overflow: 'hidden',
      display: 'flex',
      flexDirection: 'column',
      border: '1px solid rgba(226, 232, 240, 0.95)',
      opacity: '1',
      visibility: 'visible',
    } as Record<string, string>
  }

  return {
    position: 'absolute',
    right: '0',
    left: 'auto',
    bottom: '70px',
    width: '410px',
    height: '570px',
    maxHeight: 'calc(100vh - 115px)',
    background: '#ffffff',
    borderRadius: '28px',
    boxShadow: '0 28px 80px rgba(15, 23, 42, 0.26)',
    overflow: 'hidden',
    display: 'flex',
    flexDirection: 'column',
    border: '1px solid rgba(226, 232, 240, 0.95)',
    opacity: '1',
    visibility: 'visible',
  } as Record<string, string>
})

const floatingButtonOuterStyle = computed(() => {
  const buttonWidth = isMobileScreen.value ? '104px' : '124px'
  const buttonHeight = isMobileScreen.value ? '38px' : '44px'

  return {
    width: buttonWidth,
    minWidth: buttonWidth,
    maxWidth: buttonWidth,
    height: buttonHeight,
    minHeight: buttonHeight,
    maxHeight: buttonHeight,
    flex: `0 0 ${buttonWidth}`,
    borderRadius: '999px',
    padding: '3px',
    border: 'none',
    outline: 'none',
    cursor: 'pointer',
    background: '#ffffff',
    boxShadow: isMobileScreen.value
      ? '0 8px 22px rgba(14, 116, 220, 0.22), 0 0 0 1px rgba(255, 255, 255, 0.9)'
      : '0 14px 38px rgba(14, 116, 220, 0.28), 0 0 0 1px rgba(255, 255, 255, 0.95)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    margin: '0',
    overflow: 'hidden',
    visibility: 'visible',
    boxSizing: 'border-box',
    WebkitAppearance: 'none',
    appearance: 'none',
    WebkitTapHighlightColor: 'transparent',
    opacity: isLauncherIdle.value && !isOpen.value ? '0.82' : '1',
    filter: 'none',
    transform: 'none',
    animation: 'none',
    transition: 'opacity 0.35s ease, box-shadow 0.2s ease',
  } as Record<string, string>
})

const floatingButtonInnerStyle = computed(() => {
  return {
    width: '100%',
    height: '100%',
    borderRadius: '999px',
    background: 'linear-gradient(135deg, #1777ed 0%, #0b9fe8 54%, #08c7d5 100%)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: '0',
    padding: isMobileScreen.value ? '0 8px' : '0 12px',
    position: 'relative',
    overflow: 'hidden',
    boxSizing: 'border-box',
    backgroundSize: '100% 100%',
    transform: 'none',
    animation: 'none',
  } as Record<string, string>
})

const floatingButtonShineStyle: Record<string, string> = {
  position: 'absolute',
  zIndex: '0',
  top: '1px',
  right: '10%',
  left: '10%',
  height: '42%',
  borderRadius: '999px',
  background: 'linear-gradient(180deg, rgba(255, 255, 255, 0.16), transparent)',
  pointerEvents: 'none',
}

const floatingIconWrapStyle = computed(() => {
  const iconSize = isMobileScreen.value ? '24px' : '28px'

  return {
    position: 'relative',
    zIndex: '1',
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    flex: `0 0 ${iconSize}`,
    width: iconSize,
    minWidth: iconSize,
    height: iconSize,
    color: '#ffffff',
    transform: 'none',
    filter: 'none',
    animation: 'none',
  } as Record<string, string>
})

const floatingDividerStyle = computed(() => {
  return {
    position: 'relative',
    zIndex: '1',
    display: 'block',
    flex: '0 0 1px',
    width: '1px',
    minWidth: '1px',
    height: isMobileScreen.value ? '18px' : '22px',
    margin: isMobileScreen.value ? '0 6px 0 4px' : '0 9px 0 6px',
    background: 'rgba(255, 255, 255, 0.46)',
    boxShadow: '1px 0 0 rgba(7, 96, 190, 0.12)',
  } as Record<string, string>
})

const floatingTextStyle = computed(() => {
  return {
    position: 'relative',
    zIndex: '1',
    display: 'inline-block',
    color: '#ffffff',
    fontSize: isMobileScreen.value ? '11px' : '13px',
    fontWeight: '600',
    lineHeight: '1',
    letterSpacing: '0',
    whiteSpace: 'nowrap',
    transform: 'none',
    animation: 'none',
  } as Record<string, string>
})

const headerStyle = computed(() => {
  return {
    padding: isMobileScreen.value ? '14px' : '18px',
    background: 'linear-gradient(135deg, #168df2, #18c2d8)',
    color: '#ffffff',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    flexShrink: '0',
  } as Record<string, string>
})

const headerLeftStyle = {
  display: 'flex',
  alignItems: 'center',
  gap: '12px',
} as Record<string, string>

const avatarStyle = computed(() => {
  return {
    width: isMobileScreen.value ? '38px' : '44px',
    height: isMobileScreen.value ? '38px' : '44px',
    minWidth: isMobileScreen.value ? '38px' : '44px',
    minHeight: isMobileScreen.value ? '38px' : '44px',
    borderRadius: '16px',
    background: 'rgba(255, 255, 255, 0.18)',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: isMobileScreen.value ? '18px' : '20px',
  } as Record<string, string>
})

const titleStyle = computed(() => {
  return {
    fontSize: isMobileScreen.value ? '14px' : '15px',
    fontWeight: '800',
    lineHeight: '1.2',
    color: '#ffffff',
  } as Record<string, string>
})

const subtitleStyle = {
  marginTop: '2px',
  fontSize: '11px',
  opacity: '0.9',
  color: '#ffffff',
} as Record<string, string>

const headerActionsStyle = {
  display: 'flex',
  gap: '6px',
} as Record<string, string>

const iconButtonStyle = {
  width: '31px',
  height: '31px',
  minWidth: '31px',
  minHeight: '31px',
  borderRadius: '11px',
  border: 'none',
  outline: 'none',
  color: '#ffffff',
  background: 'rgba(255, 255, 255, 0.18)',
  cursor: 'pointer',
  fontSize: '13px',
  padding: '0',
} as Record<string, string>

const bodyStyle = computed(() => {
  return {
    flex: '1',
    padding: isMobileScreen.value ? '14px' : '18px',
    overflowY: 'auto',
    background: 'linear-gradient(180deg, #f8fbff 0%, #ffffff 100%)',
  } as Record<string, string>
})

const disclaimerStyle = {
  marginBottom: '12px',
  padding: '10px 12px',
  borderRadius: '14px',
  background: '#fff7ed',
  border: '1px solid #fed7aa',
  color: '#9a3412',
  fontSize: '11px',
  lineHeight: '1.45',
  fontWeight: '600',
} as Record<string, string>

const footerStyle = computed(() => {
  return {
    padding: isMobileScreen.value ? '10px' : '13px',
    display: 'flex',
    gap: '10px',
    alignItems: 'flex-end',
    borderTop: '1px solid #e2e8f0',
    background: '#ffffff',
    flexShrink: '0',
  } as Record<string, string>
})

const inputStyle = computed(() => {
  return {
    flex: '1',
    minHeight: isMobileScreen.value ? '38px' : '42px',
    maxHeight: '88px',
    resize: 'none',
    borderRadius: '16px',
    border: '1px solid #dbeafe',
    background: '#f8fbff',
    outline: 'none',
    padding: isMobileScreen.value ? '9px 12px' : '11px 13px',
    fontSize: isMobileScreen.value ? '12px' : '13px',
    color: '#1f2937',
    width: 'auto',
  } as Record<string, string>
})

const sendButtonStyle = computed(() => {
  return {
    width: isMobileScreen.value ? '38px' : '42px',
    height: isMobileScreen.value ? '38px' : '42px',
    minWidth: isMobileScreen.value ? '38px' : '42px',
    minHeight: isMobileScreen.value ? '38px' : '42px',
    border: 'none',
    borderRadius: '15px',
    outline: 'none',
    background:
      isLoading.value || !inputMessage.value.trim()
        ? '#94a3b8'
        : 'linear-gradient(135deg, #168df2, #18c2d8)',
    color: '#ffffff',
    cursor: isLoading.value || !inputMessage.value.trim() ? 'not-allowed' : 'pointer',
    boxShadow:
      isLoading.value || !inputMessage.value.trim()
        ? 'none'
        : '0 10px 22px rgba(0, 124, 219, 0.25)',
    fontSize: isMobileScreen.value ? '15px' : '17px',
    padding: '0',
  } as Record<string, string>
})

const faqSectionStyle = computed(() => {
  return {
    marginTop: '14px',
    padding: isFaqOpen.value ? '14px' : '10px 12px',
    borderRadius: '18px',
    background: '#f8fafc',
    border: '1px solid #e2e8f0',
    transition: 'all 0.22s ease',
  } as Record<string, string>
})

const faqToggleStyle = computed(() => {
  return {
    width: '100%',
    border: 'none',
    background: 'transparent',
    padding: '0',
    margin: '0',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    cursor: 'pointer',
    textAlign: 'left',
    gap: '10px',
  } as Record<string, string>
})

const faqTitleWrapStyle = {
  display: 'flex',
  flexDirection: 'column',
  gap: '2px',
} as Record<string, string>

const faqTitleStyle = {
  fontSize: '12px',
  fontWeight: '800',
  color: '#334155',
} as Record<string, string>

const faqHintStyle = {
  fontSize: '10px',
  fontWeight: '600',
  color: '#64748b',
} as Record<string, string>

const faqArrowStyle = computed(() => {
  return {
    width: '26px',
    height: '26px',
    minWidth: '26px',
    borderRadius: '999px',
    background: isFaqOpen.value ? '#e0f2fe' : '#ffffff',
    border: '1px solid #bae6fd',
    color: '#0284c7',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: '13px',
    fontWeight: '900',
    transition: 'all 0.2s ease',
    transform: isFaqOpen.value ? 'rotate(180deg)' : 'rotate(0deg)',
  } as Record<string, string>
})

const faqContentStyle = {
  marginTop: '12px',
} as Record<string, string>

const faqListStyle = {
  display: 'flex',
  flexWrap: 'wrap',
  gap: '8px',
} as Record<string, string>

const faqChipStyle = {
  padding: '7px 10px',
  borderRadius: '999px',
  border: '1px solid #bae6fd',
  background: '#ffffff',
  color: '#0369a1',
  fontSize: '11px',
  fontWeight: '700',
  cursor: 'pointer',
  transition: 'all 0.16s ease',
} as Record<string, string>

const numberedIntroStyle = {
  marginBottom: '9px',
  fontSize: '13px',
  lineHeight: '1.65',
  fontWeight: '700',
  color: '#1e293b',
} as Record<string, string>

const numberedListStyle = {
  margin: '0',
  paddingLeft: '22px',
  display: 'flex',
  flexDirection: 'column',
  gap: '8px',
} as Record<string, string>

const numberedItemStyle = {
  paddingLeft: '2px',
  fontSize: '13px',
  lineHeight: '1.7',
  color: '#1e293b',
} as Record<string, string>

const getMessageRowStyle = (role: ChatRole) => {
  return {
    display: 'flex',
    marginBottom: '12px',
    justifyContent: role === 'user' ? 'flex-end' : 'flex-start',
  } as Record<string, string>
}

const getMessageBubbleStyle = (role: ChatRole) => {
  return {
    maxWidth: isMobileScreen.value ? '90%' : '82%',
    padding: isMobileScreen.value ? '10px 12px' : '11px 13px',
    borderRadius: '17px',
    fontSize: isMobileScreen.value ? '12px' : '13px',
    lineHeight: '1.6',
    whiteSpace: 'pre-wrap',
    wordBreak: 'break-word',
    color: role === 'user' ? '#ffffff' : '#1f2937',
    background:
      role === 'user'
        ? 'linear-gradient(135deg, #168df2, #18c2d8)'
        : '#eef7ff',
    borderBottomRightRadius: role === 'user' ? '6px' : '17px',
    borderBottomLeftRadius: role === 'bot' ? '6px' : '17px',
  } as Record<string, string>
}

const metaStyle = {
  marginTop: '7px',
  paddingTop: '6px',
  borderTop: '1px solid rgba(148, 163, 184, 0.22)',
  fontSize: '10px',
  color: '#64748b',
} as Record<string, string>

const normalizeAnswer = (text: string) => {
  if (!text) return 'Maaf, saya belum mendapatkan jawaban dari sistem.'

  return text
    .replace(/\s+/g, ' ')
    .replace(/\s+\./g, '.')
    .replace(/\s+,/g, ',')
    .replace(/\s+:/g, ':')
    .trim()
}

const parseNumberedMessage = (text: string): ParsedMessage => {
  const cleaned = normalizeAnswer(text)
  const matches = Array.from(cleaned.matchAll(/(?:^|\s)(\d+)\.\s+/g))

  if (matches.length < 2) {
    return {
      type: 'text',
      text: cleaned,
      intro: '',
      items: [],
    }
  }

  const firstMatch = matches[0]
  const intro = cleaned.slice(0, firstMatch.index || 0).trim()

  const items = matches
    .map((match, index) => {
      const start = (match.index || 0) + match[0].length
      const end =
        index + 1 < matches.length
          ? matches[index + 1].index || cleaned.length
          : cleaned.length

      return cleaned
        .slice(start, end)
        .trim()
        .replace(/^[,;:\s]+/g, '')
        .replace(/[,;:\s]+$/g, '')
    })
    .filter((item) => item.length > 0)

  if (items.length < 2) {
    return {
      type: 'text',
      text: cleaned,
      intro: '',
      items: [],
    }
  }

  return {
    type: 'numbered',
    text: cleaned,
    intro,
    items,
  }
}

const renderedMessages = computed(() => {
  return messages.value.map((message) => ({
    ...message,
    parsed: parseNumberedMessage(message.text),
  }))
})

const toggleFaq = async () => {
  isFaqOpen.value = !isFaqOpen.value
  await nextTick()
}

const openByHover = () => {
  if (!shouldShowWidget.value) return
  if (!isMobileScreen.value) {
    isOpen.value = true
  }
}

const closeByLeave = () => {
  if (isMobileScreen.value) return
  if (isPinned.value) return
  isOpen.value = false
}

const togglePinned = () => {
  if (!shouldShowWidget.value) return

  if (isMobileScreen.value) {
    isOpen.value = !isOpen.value
    isPinned.value = isOpen.value
    return
  }

  isPinned.value = !isPinned.value
  isOpen.value = true
}

const handleLauncherClick = () => {
  clearLauncherIdleTimer()
  isLauncherIdle.value = false
  togglePinned()
}

const closeChat = () => {
  isPinned.value = false
  isOpen.value = false
}

const scrollToBottom = async () => {
  await nextTick()
  if (chatBodyRef.value) {
    chatBodyRef.value.scrollTop = chatBodyRef.value.scrollHeight
  }
}

const addMessage = async (role: ChatRole, text: string, extra?: Partial<ChatMessage>) => {
  if (role === 'user') {
    isFaqOpen.value = false
  }

  messages.value.push({
    id: Date.now() + Math.floor(Math.random() * 1000),
    role,
    text: normalizeAnswer(text),
    ...extra,
  })

  await scrollToBottom()
}

const getAuthToken = () => {
  const possibleKeys = ['token', 'user_token', 'access_token', 'auth_token', 'jwt']

  for (const key of possibleKeys) {
    const localValue = localStorage.getItem(key)
    if (localValue) return localValue

    const sessionValue = sessionStorage.getItem(key)
    if (sessionValue) return sessionValue
  }

  return ''
}

const sendMessage = async (messageText?: string) => {
  const question = String(messageText || inputMessage.value || '').trim()

  if (!question || isLoading.value) return

  inputMessage.value = ''
  await addMessage('user', question)

  isLoading.value = true

  try {
    const token = getAuthToken()

    const headers: Record<string, string> = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    }

    if (token) {
      headers.Authorization = `Bearer ${token}`
      headers.token = token
    }

    const response = await fetch(CHATBOT_URL, {
      method: 'POST',
      credentials: 'include',
      headers,
      body: JSON.stringify({
        message: question,
        kelompokUser: normalizedKelompokUser.value || 'customer',
      }),
    })

    let data: ChatbotResponse = {}

    try {
      data = await response.json()
    } catch (error) {
      data = {}
    }

    if (!response.ok) {
      await addMessage(
        'bot',
        'Maaf, chatbot belum bisa memproses pertanyaan saat ini. Silakan coba beberapa saat lagi.'
      )
      return
    }

    await addMessage('bot', data.answer || '', {
      mode: data.mode,
      intent: data.intent,
    })
  } catch (error) {
    await addMessage(
      'bot',
      'Maaf, koneksi ke chatbot belum berhasil. Pastikan koneksi internet aktif dan coba lagi.'
    )
  } finally {
    isLoading.value = false
    await scrollToBottom()
  }
}

const onEnter = (event: KeyboardEvent) => {
  if (event.shiftKey) return
  event.preventDefault()
  sendMessage()
}
</script>

<template>
  <Teleport to="body">
    <div v-if="shouldShowWidget" id="ulab-ai-chatbot-widget" :style="rootStyle" @mouseenter="openByHover"
      @mouseleave="closeByLeave">
      <transition name="ulab-chatbot-pop">
        <div v-if="isOpen" :style="panelStyle">
          <div :style="headerStyle">
            <div :style="headerLeftStyle">
              <!-- <div :style="avatarStyle">🤖</div> -->

              <div>
                <div :style="titleStyle">U-LAB Assistant</div>
                <div :style="subtitleStyle">{{ chatbotSubtitle }}</div>
              </div>
            </div>

            <div :style="headerActionsStyle">
              <button v-if="!isMobileScreen" type="button" :style="iconButtonStyle" title="Tetapkan chat"
                @click="togglePinned">
                📌
              </button>

              <button type="button" :style="iconButtonStyle" title="Tutup" @click="closeChat">
                ✕
              </button>
            </div>
          </div>

          <div ref="chatBodyRef" :style="bodyStyle">
            <div :style="disclaimerStyle">
              Informasi dari AI mungkin tidak akurat. Silakan verifikasi kembali.
            </div>

            <div v-for="message in renderedMessages" :key="message.id" :style="getMessageRowStyle(message.role)">
              <div :style="getMessageBubbleStyle(message.role)">
                <template v-if="message.role === 'bot' && message.parsed.type === 'numbered'">
                  <div v-if="message.parsed.intro" :style="numberedIntroStyle">
                    {{ message.parsed.intro }}
                  </div>

                  <ol :style="numberedListStyle">
                    <li v-for="(item, itemIndex) in message.parsed.items" :key="`${message.id}-${itemIndex}`"
                      :style="numberedItemStyle">
                      {{ item }}
                    </li>
                  </ol>
                </template>

                <template v-else>
                  {{ message.parsed.text }}
                </template>

                <div v-if="message.role === 'bot' && message.mode" :style="metaStyle">
                  {{ message.mode === 'database' ? 'Data sistem U-LAB' : 'Manual U-LAB' }}
                </div>
              </div>
            </div>

            <div v-if="isLoading" :style="getMessageRowStyle('bot')">
              <div :style="getMessageBubbleStyle('bot')">
                Sedang memproses...
              </div>
            </div>

            <div :style="faqSectionStyle">
              <button type="button" :style="faqToggleStyle" @click="toggleFaq">
                <span :style="faqTitleWrapStyle">
                  <span :style="faqTitleStyle">{{ faqTitleText }}</span>

                  <span v-if="hasUserQuestion && !isFaqOpen" :style="faqHintStyle">
                    Dilipat agar jawaban lebih terlihat. Klik untuk buka.
                  </span>
                </span>

                <span :style="faqArrowStyle">˅</span>
              </button>

              <transition name="ulab-faq-fold">
                <div v-show="isFaqOpen" :style="faqContentStyle">
                  <div :style="faqListStyle">
                    <button v-for="faq in quickFaq" :key="faq" type="button" :style="faqChipStyle" :disabled="isLoading"
                      @click="sendMessage(faq)">
                      {{ faq }}
                    </button>
                  </div>
                </div>
              </transition>
            </div>
          </div>

          <div :style="footerStyle">
            <textarea v-model="inputMessage" rows="1" :style="inputStyle" placeholder="Tanyakan sesuatu..."
              :disabled="isLoading" @keydown.enter="onEnter"></textarea>

            <button type="button" :style="sendButtonStyle" :disabled="isLoading || !inputMessage.trim()"
              @click="sendMessage()">
              <span v-if="!isLoading">➤</span>
              <span v-else>⟳</span>
            </button>
          </div>
        </div>
      </transition>

      <button type="button" class="ulab-tanya-button"
        :class="{ 'is-mobile-chatbot-button': isMobileScreen, 'is-idle': isLauncherIdle && !isOpen }"
        :style="floatingButtonOuterStyle" title="Tanya asisten U-LAB" @click="handleLauncherClick">
        <span class="ulab-tanya-inner" :style="floatingButtonInnerStyle">
          <span class="ulab-tanya-shine" :style="floatingButtonShineStyle" aria-hidden="true"></span>

          <span class="ulab-tanya-icon-wrap" :style="floatingIconWrapStyle">
            <svg class="ulab-tanya-icon" :width="isMobileScreen ? 21 : 24"
              :height="isMobileScreen ? 21 : 24" viewBox="0 0 48 48" fill="none"
              xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
              style="display: block; flex: none; color: #ffffff; fill: none; transform: none">
              <path
                class="ulab-tanya-bubble"
                d="M8 8.5C8 5.46 10.46 3 13.5 3h21C37.54 3 40 5.46 40 8.5v19c0 3.04-2.46 5.5-5.5 5.5H20.2L10 41v-8.95a5.5 5.5 0 0 1-2-4.25V8.5Z"
                fill="#ffffff" style="fill: #ffffff !important" />
              <circle class="ulab-tanya-dot" cx="17" cy="18" r="2.6" fill="#158eea"
                style="fill: #158eea !important" />
              <circle class="ulab-tanya-dot" cx="24" cy="18" r="2.6" fill="#158eea"
                style="fill: #158eea !important" />
              <circle class="ulab-tanya-dot" cx="31" cy="18" r="2.6" fill="#158eea"
                style="fill: #158eea !important" />
            </svg>
          </span>

          <span class="ulab-tanya-divider" :style="floatingDividerStyle" aria-hidden="true"></span>

          <span class="ulab-tanya-label" :style="floatingTextStyle">
            Tanya
          </span>
        </span>
      </button>
    </div>
  </Teleport>
</template>

<style lang="scss">
#ulab-ai-chatbot-widget,
#ulab-ai-chatbot-widget * {
  box-sizing: border-box;
}

.ulab-chatbot-pop-enter-active,
.ulab-chatbot-pop-leave-active {
  transition: all 0.2s ease;
}

.ulab-chatbot-pop-enter-from,
.ulab-chatbot-pop-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.96);
}

.ulab-faq-fold-enter-active,
.ulab-faq-fold-leave-active {
  transition:
    opacity 0.2s ease,
    max-height 0.25s ease,
    transform 0.2s ease;
  overflow: hidden;
  max-height: 260px;
}

.ulab-faq-fold-enter-from,
.ulab-faq-fold-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-6px);
}

.ulab-tanya-button {
  -webkit-tap-highlight-color: transparent;
  opacity: 1;
  filter: none;
  transform: none;
  animation: none;
  transition:
    box-shadow 0.25s ease,
    opacity 0.35s ease;
}

.ulab-tanya-button.is-idle {
  opacity: 0.82;
  filter: none;
  transform: none;
}

.ulab-tanya-button:hover {
  transform: none;
}

.ulab-tanya-button:active {
  transform: none;
}

.ulab-tanya-button:focus-visible {
  outline: 3px solid rgba(14, 165, 233, 0.3) !important;
  outline-offset: 4px;
}

.ulab-tanya-inner {
  position: relative;
  overflow: hidden;
  background-size: 100% 100% !important;
  transform: none;
  animation: none;
}

.ulab-tanya-inner::after {
  display: none;
}

.ulab-tanya-icon-wrap {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 28px;
  width: 28px;
  height: 28px;
  color: #ffffff;
  transform: none;
  filter: none;
  animation: none;
}

.ulab-tanya-icon {
  display: block;
  color: #ffffff !important;
  transform: none;
  filter: none;
  animation: none;
}

#ulab-ai-chatbot-widget .ulab-tanya-icon .ulab-tanya-bubble {
  fill: #ffffff !important;
}

#ulab-ai-chatbot-widget .ulab-tanya-icon .ulab-tanya-dot {
  fill: #158eea !important;
}

.ulab-tanya-divider {
  position: relative;
  z-index: 1;
  display: block;
  flex: 0 0 1px;
  width: 1px;
  height: 22px;
  margin: 0 9px 0 6px;
  background: rgba(255, 255, 255, 0.46);
  box-shadow: 1px 0 0 rgba(7, 96, 190, 0.12);
}

.ulab-tanya-label {
  position: relative;
  z-index: 1;
  display: inline-block;
  transform: none;
  animation: none;
}

@media (max-width: 768px) {
  .ulab-tanya-button:hover {
    transform: none !important;
  }

  .ulab-tanya-button:hover .ulab-tanya-icon-wrap {
    transform: none !important;
  }

  .ulab-tanya-button:hover .ulab-tanya-icon {
    transform: none !important;
    filter: none !important;
  }

  .ulab-tanya-button:hover .ulab-tanya-label {
    transform: none !important;
  }

  .ulab-tanya-icon-wrap {
    flex-basis: 24px;
    width: 24px;
    height: 24px;
  }

  .ulab-tanya-divider {
    height: 18px;
    margin: 0 6px 0 4px;
  }
}
</style>
