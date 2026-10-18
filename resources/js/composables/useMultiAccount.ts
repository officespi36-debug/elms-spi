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

    const currentEntry: SavedAccount = {
      id: currentUser.id,
      name: currentUser.name || (existing ? existing.name : ''),
      name_kh: currentUser.name_kh || (existing ? existing.name_kh : ''),
      email: currentUser.email || (existing ? existing.email : ''),
      role: currentUser.role || (existing ? existing.role : 'admin'),
      avatar: currentUser.avatar || (existing ? existing.avatar : ''),
      switch_token: existing?.switch_token || '',
      last_active: Date.now(),
    }

    if (existingIndex !== -1) {
      savedAccounts.value[existingIndex] = currentEntry
    } else {
      savedAccounts.value.unshift(currentEntry)
    }
    persistStorage()

    // Always fetch latest token & suggested accounts from server
    try {
      const res = await fetch('/api/auth/multi-account/current-token', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      if (res.ok) {
        const data = await res.json()
        if (data.success) {
          if (data.switch_token) {
            const idx = savedAccounts.value.findIndex(a => a.id === currentUser.id)
            if (idx !== -1) {
              savedAccounts.value[idx].switch_token = data.switch_token
            }
          }

          if (data.suggested_accounts && Array.isArray(data.suggested_accounts)) {
            for (const sug of data.suggested_accounts) {
              const sIdx = savedAccounts.value.findIndex(a => a.id === sug.id || a.email === sug.email)
              if (sIdx === -1) {
                savedAccounts.value.push(sug)
              } else {
                savedAccounts.value[sIdx] = {
                  ...savedAccounts.value[sIdx],
                  ...sug,
                  switch_token: sug.switch_token || savedAccounts.value[sIdx].switch_token,
                }
              }
            }
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
        credentials: 'same-origin',
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
    isSwitching.value = true
    activeSwitchingId.value = targetAccount.id
    addError.value = null

    try {
      const response = await fetch('/api/auth/multi-account/switch', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          user_id: targetAccount.id,
          switch_token: targetAccount.switch_token || '',
        }),
      })

      const data = await response.json()

      if (!response.ok || !data.success) {
        addError.value = data.message || 'Session expired. Please re-login.'
        isSwitching.value = false
        activeSwitchingId.value = null
        return false
      }

      // Update last active and switch_token
      targetAccount.last_active = Date.now()
      if (data.switch_token) {
        targetAccount.switch_token = data.switch_token
      }
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
