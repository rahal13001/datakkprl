import { Capacitor } from '@capacitor/core'

const prefix = 'servicekkprl.'
const nativePluginPrefix = `capacitor-storage_${prefix}`

interface NativeBridge {
  nativePromise<T>(
    pluginName: string,
    methodName: string,
    options: Record<string, unknown>,
  ): Promise<T>
}

interface NativeGetResult {
  data: string | null
}

interface NativeKeysResult {
  keys: string[]
}

function nativeBridge(): NativeBridge {
  return Capacitor as typeof Capacitor & NativeBridge
}

function nativeKey(key: string): string {
  return `${nativePluginPrefix}${key}`
}

async function nativeCall<T>(
  methodName: string,
  options: Record<string, unknown>,
): Promise<T> {
  // Capacitor 8 currently treats some plugin proxies as Promise-like objects,
  // which can leave the public secure-storage wrapper pending indefinitely.
  // Calling the registered native plugin method directly preserves the same
  // Android Keystore/iOS Keychain implementation without crossing that proxy.
  // https://github.com/ionic-team/capacitor/issues/8469
  return nativeBridge().nativePromise<T>('SecureStorage', methodName, options)
}

export const secureStorage = {
  async get<T>(key: string): Promise<T | null> {
    if (!Capacitor.isNativePlatform()) {
      const value = sessionStorage.getItem(prefix + key)
      return value ? (JSON.parse(value) as T) : null
    }

    const result = await nativeCall<NativeGetResult>('internalGetItem', {
      prefixedKey: nativeKey(key),
      sync: false,
    })

    return result.data === null ? null : (JSON.parse(result.data) as T)
  },

  async set(key: string, value: unknown): Promise<void> {
    if (!Capacitor.isNativePlatform()) {
      sessionStorage.setItem(prefix + key, JSON.stringify(value))
      return
    }

    await nativeCall<void>('internalSetItem', {
      prefixedKey: nativeKey(key),
      data: JSON.stringify(value),
      sync: false,
      access: 0,
    })
  },

  async remove(key: string): Promise<void> {
    if (!Capacitor.isNativePlatform()) {
      sessionStorage.removeItem(prefix + key)
      return
    }

    await nativeCall('internalRemoveItem', {
      prefixedKey: nativeKey(key),
      sync: false,
    })
  },

  async clear(): Promise<void> {
    if (!Capacitor.isNativePlatform()) {
      Object.keys(sessionStorage)
        .filter((key) => key.startsWith(prefix))
        .forEach((key) => sessionStorage.removeItem(key))
      return
    }

    const result = await nativeCall<NativeKeysResult>('getPrefixedKeys', {
      prefix: nativePluginPrefix,
      sync: false,
    })

    for (const prefixedKey of result.keys) {
      await nativeCall('internalRemoveItem', {
        prefixedKey,
        sync: false,
      })
    }
  },
}
