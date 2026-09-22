import { ref, computed } from 'vue'

export interface SavedAccount {
  id: number
  name: string
  name_kh?: string
  email: string
  role: string
  avatar?: string
  switch_token?: string
  last_active?: number
}

const STORAGE_KEY = 'elms_saved_accounts'

const savedAccounts = ref<SavedAccount[]>([])
const isAdding = ref(false)
const isSwitching = ref(false)
const activeSwitchingId = ref<number | null>(null)
const addError = ref<string | null>(null)
let isInitialized = false

const getCsrfToken = (): string => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
}

const loadFromStorage = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) {
      savedAccounts.value = JSON.parse(raw)
    }
  } catch (e) {
    savedAccounts.value = []
  }
}

const persistStorage = () => {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(savedAccounts.value))
  } catch (e) {
    // ignore
  }
}

export function useMultiAccount() {
  if (!isInitialized) {
    loadFromStorage()
    isInitialized = true
  }

  /**
   * Synchronize currently logged-in user into the saved accounts list.
   * Also fetches switch token from server if missing.
   */
  const syncCurrentUser = async (currentUser: any) => {
    if (!currentUser || !currentUser.id) return

    loadFromStorage()

    const existingIndex = savedAccounts.value.findIndex(a => a.id === currentUser.id)
    const existing = existingIndex !== -1 ? savedAccounts.value[existingIndex] : null

    // If account exists and already has switch_token, just update metadata
    if (existing && existing.switch_token) {
      savedAccounts.value[existingIndex] = {
        ...existing,
        name: currentUser.name || existing.name,
        name_kh: currentUser.name_kh || existing.name_kh,
        email: currentUser.email || existing.email,
        role: currentUser.role || existing.role,
        avatar: currentUser.avatar || existing.avatar,
        last_active: Date.now(),
      }
      persistStorage()
      return
    }

    // Otherwise fetch switch token from server to enable seamless 1-click switching back later
    try {
      const res = await fetch('/api/auth/multi-account/current-token', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      if (res.ok) {
        const data = await res.json()
        if (data.success && data.switch_token) {
          const newEntry: SavedAccount = {
            id: currentUser.id,
            name: currentUser.name,
            name_kh: currentUser.name_kh,
            email: currentUser.email,
            role: currentUser.role,
            avatar: currentUser.avatar,
            switch_token: data.switch_token,
            last_active: Date.now(),
          }

          if (existingIndex !== -1) {
            savedAccounts.value[existingIndex] = newEntry
          } else {
            savedAccounts.value.unshift(newEntry)
          }
          persistStorage()
        }
      }
    } catch (e) {
      // silent
    }
  }

  /**
   * Authenticate and add another account into the switcher.
   */
  const addAccount = async (identifier: string, password: string): Promise<boolean> => {
    isAdding.value = true
    addError.value = null

    try {
      const response = await fetch('/api/auth/multi-account/add', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          identifier: identifier.trim(),
          password: password,
        }),
      })

      const data = await response.json()

      if (!response.ok || !data.success) {
        addError.value = data.message || 'Failed to add account. Please check credentials.'
        isAdding.value = false
        return false
      }

      // Add to saved accounts list
      const newUser = data.user
      const newEntry: SavedAccount = {
        id: newUser.id,
        name: newUser.name,
        name_kh: newUser.name_kh,
        email: newUser.email,
        role: newUser.role,
        avatar: newUser.avatar,
        switch_token: data.switch_token,
        last_active: Date.now(),
      }

      const existingIndex = savedAccounts.value.findIndex(a => a.id === newUser.id)
      if (existingIndex !== -1) {
        savedAccounts.value[existingIndex] = newEntry
      } else {
        savedAccounts.value.unshift(newEntry)
      }
      persistStorage()

      // Redirect directly to the dashboard of the newly logged-in role
      if (data.redirect) {
        window.location.href = data.redirect
      } else {
        window.location.reload()
      }

      return true
    } catch (e: any) {
      addError.value = 'Connection error. Please try again.'
      isAdding.value = false
      return false
    }
  }

  /**
   * 1-Click switch to a saved account.
   */
  const switchAccount = async (targetAccount: SavedAccount): Promise<boolean> => {
    if (!targetAccount.switch_token) {
      addError.value = 'Password required for this account.'
      return false
    }

    isSwitching.value = true
    activeSwitchingId.value = targetAccount.id
    addError.value = null

    try {
      const response = await fetch('/api/auth/multi-account/switch', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          user_id: targetAccount.id,
          switch_token: targetAccount.switch_token,
        }),
      })

      const data = await response.json()

      if (!response.ok || !data.success) {
        addError.value = data.message || 'Session expired. Please re-login.'
        isSwitching.value = false
        activeSwitchingId.value = null
        return false
      }

      // Update last active
      targetAccount.last_active = Date.now()
      persistStorage()

      // Navigate to target dashboard
      if (data.redirect) {
        window.location.href = data.redirect
      } else {
        window.location.reload()
      }

      return true
    } catch (e: any) {
      addError.value = 'Connection error during account switch.'
      isSwitching.value = false
      activeSwitchingId.value = null
      return false
    }
  }

  /**
   * Remove/forget account from saved list on this device.
   */
  const removeAccount = (id: number) => {
    savedAccounts.value = savedAccounts.value.filter(a => a.id !== id)
    persistStorage()
  }

  return {
    savedAccounts,
    isAdding,
    isSwitching,
    activeSwitchingId,
    addError,
    syncCurrentUser,
    addAccount,
    switchAccount,
    removeAccount,
  }
}
