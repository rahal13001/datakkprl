import { AxiosError, AxiosHeaders } from 'axios'
import { describe, expect, it } from 'vitest'
import { apiError } from '@/api/client'

describe('apiError', () => {
  it('flattens Laravel validation messages for an officer', () => {
    const error = new AxiosError('Request failed', '422', undefined, undefined, {
      data: {
        message: 'The submitted data is invalid.',
        code: 'validation_failed',
        errors: {
          date: ['Tanggal wajib diisi.'],
          end_time: ['Waktu selesai harus setelah waktu mulai.'],
        },
      },
      status: 422,
      statusText: 'Unprocessable Content',
      headers: new AxiosHeaders(),
      config: { headers: new AxiosHeaders() },
    })

    expect(apiError(error)).toBe(
      'Tanggal wajib diisi. Waktu selesai harus setelah waktu mulai.',
    )
  })
})
